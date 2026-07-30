<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Filament\Resources\ContactMessageResource\Pages\ListContactMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\AuditLog;
use Livewire\Livewire;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_get_form_can_be_rendered()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('Hubungi Kami');
        $response->assertSee('cf-turnstile-response');
    }

    public function test_valid_post_and_prg_redirect()
    {
        config(['services.turnstile.secret' => 'secret']);
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'contact_type' => 'email',
            'contact_value' => 'john@example.com',
            'subject' => 'Pertanyaan',
            'message' => 'Ini adalah pesan yang valid minimum 10 karakter.',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect('/kontak');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'contact_type' => 'email',
            'contact_value' => 'john@example.com',
            'status' => 'new',
        ]);
        
        $msg = ContactMessage::first();
        $this->assertEquals("Ini adalah pesan yang valid minimum 10 karakter.", $msg->message);
    }

    public function test_validation_bounds()
    {
        $response = $this->post('/kontak', [
            'name' => 'a',
            'contact_type' => 'email',
            'contact_value' => 'invalid-email',
            'subject' => '',
            'message' => 'short',
            'cf-turnstile-response' => 'valid',
        ]);

        $response->assertSessionHasErrors(['name', 'contact_value', 'subject', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_html_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'contact_type' => 'email',
            'contact_value' => 'john@example.com',
            'subject' => 'Pertanyaan',
            'message' => 'Ini <b>bold</b> dan <script>alert(1)</script>',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors(['message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_admin_requires_authentication()
    {
        $adminPath = config('village.admin_path', 'desa-dashboard');
        $response = $this->get("/{$adminPath}/contact-messages");
        $response->assertRedirect("/{$adminPath}/login");
    }

    public function test_admin_can_view_inbox_and_archive()
    {
        $admin = Admin::factory()->create();
        $message = ContactMessage::create([
            'name' => 'Jane',
            'contact_type' => 'phone',
            'contact_value' => '0812345678',
            'subject' => 'Test Subject',
            'message' => 'Test message length is 10+',
        ]);

        $adminPath = config('village.admin_path', 'desa-dashboard');

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->assertSee('Jane')
            ->assertSee('0812***') // Masked in table
            ->assertDontSee('0812345678') // Unmasked value
            ->assertSee('Telepon/WhatsApp')
            ->assertTableActionVisible('mark_read', $message)
            ->callTableAction('mark_read', $message)
            ->assertHasNoTableActionErrors();
            
        $message->refresh();
        $this->assertNotNull($message->read_at);
        $this->assertEquals(ContactStatus::READ, $message->status);
        
        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ContactMessageResource\Pages\ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertSee('Jane')
            ->assertSee('0812345678') // Unmasked value on detail page
            ->assertSee('Telepon/WhatsApp')
            ->assertSee('Test message length is 10+');

        // mark_read should not downgrade status if it is already IN_PROGRESS or RESOLVED
        $message->changeStatus(ContactStatus::IN_PROGRESS);
        $message->markAsRead(); // manual call to ensure it doesn't change it
        $this->assertEquals(ContactStatus::IN_PROGRESS, $message->status);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('change_status', $message, data: ['status' => 'resolved'])
            ->assertHasNoTableActionErrors();
            
        $message->refresh();
        $this->assertEquals(ContactStatus::RESOLVED, $message->status);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$message])
            ->callTableAction('archive', $message)
            ->assertHasNoTableActionErrors()
            ->assertCanNotSeeTableRecords([$message]);
            
        $message->refresh();
        $this->assertNotNull($message->archived_at);
        $this->assertEquals(ContactStatus::RESOLVED, $message->status); // Status preserved

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->set('activeTab', 'archived')
            ->assertCanSeeTableRecords([$message])
            ->callTableAction('restore', $message)
            ->assertHasNoTableActionErrors()
            ->assertCanNotSeeTableRecords([$message]);
            
        $message->refresh();
        $this->assertNull($message->archived_at);
        $this->assertEquals(ContactStatus::RESOLVED, $message->status);
    }

    public function test_audit_logs_redact_pii()
    {
        $admin = Admin::factory()->create();
        
        $message = ContactMessage::create([
            'name' => 'Secret Name',
            'contact_type' => 'email',
            'contact_value' => 'secret@example.com',
            'subject' => 'Secret Subject',
            'message' => 'This is a very secret message.',
        ]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('archive', $message);

        $logs = AuditLog::where('subject_id', $message->id)->get();
        $this->assertTrue($logs->count() > 0);

        foreach ($logs as $log) {
            $payload = json_encode($log->new_values) . json_encode($log->old_values);
            $this->assertStringNotContainsString('Secret Name', $payload);
            $this->assertStringNotContainsString('secret@example.com', $payload);
            $this->assertStringNotContainsString('Secret Subject', $payload);
            $this->assertStringNotContainsString('This is a very secret message', $payload);
            
            if ($log->event === 'updated') {
                $this->assertArrayHasKey('archived_at', $log->new_values);
                $this->assertArrayNotHasKey('name', $log->new_values);
                $this->assertArrayNotHasKey('subject', $log->new_values);
                $this->assertArrayNotHasKey('message', $log->new_values);
                $this->assertArrayNotHasKey('contact_value', $log->new_values);
            }
        }
    }

    public function test_message_detail_presentation_on_view()
    {
        $admin = \App\Models\Admin::factory()->create();
        $message = \App\Models\ContactMessage::create([
            "name" => "Jane",
            "contact_type" => "phone",
            "contact_value" => "0812345678",
            "subject" => "Test Subject",
            "message" => "Baris satu\nBaris dua <b>bold</b>",
        ]);

        $livewire = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ContactMessageResource\Pages\ViewContactMessage::class, ["record" => $message->getRouteKey()]);

        $schema = \App\Filament\Resources\ContactMessageResource::infolist(new \Filament\Schemas\Schema($livewire->instance()));
        $components = $schema->getComponents();
        
        $messageEntry = null;
        foreach ($components as $component) {
            if ($component instanceof \Filament\Schemas\Components\View && $component->getView() === 'filament.schemas.components.contact-message-card') {
                $messageEntry = $component;
                break;
            }
        }
        
        $this->assertNotNull($messageEntry);

        $livewire
            ->assertSeeHtml("Baris satu")
            ->assertSeeHtml("Baris dua &lt;b&gt;bold&lt;/b&gt;")
            ->assertSeeHtml("white-space: pre-wrap;")
            ->assertSeeHtml("text-align: left;")
            ->assertDontSeeHtml("text-center")
            ->assertDontSeeHtml("items-center")
            ->assertDontSeeHtml("justify-center");
    }
}
