<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactMessageSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.secret' => 'secret']);
        $this->withoutVite();
    }

    public function test_rate_limiting_blocks_sixth_request_in_15_minutes()
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $ip = '127.0.0.1';
        $key = 'contact-submissions:' . $ip;
        
        // Ensure we are testing exactly the implementation key logic
        // But since we can't reliably predict the hash if it's hashed, we just run the requests.

        for ($i = 0; $i < 5; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/kontak', [
                'name' => 'John Doe ' . $i,
                'contact_type' => 'email',
                'contact_value' => 'john@example.com',
                'subject' => 'Pertanyaan',
                'message' => 'Ini adalah pesan yang valid minimum 10 karakter.',
                'cf-turnstile-response' => 'valid-token',
            ]);
            $response->assertRedirect('/kontak');
        }

        // 6th request should fail
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/kontak', [
            'name' => 'John Doe 6',
            'contact_type' => 'email',
            'contact_value' => 'john@example.com',
            'subject' => 'Pertanyaan',
            'message' => 'Ini adalah pesan yang valid minimum 10 karakter.',
            'cf-turnstile-response' => 'valid-token',
        ]);
        
        $response->assertStatus(429); // Too Many Requests
        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_cannot_mass_assign_restricted_fields()
    {
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
            'status' => 'resolved', // should be ignored
            'read_at' => now()->toDateTimeString(), // should be ignored
            'archived_at' => now()->toDateTimeString(), // should be ignored
        ]);

        $response->assertRedirect('/kontak');
        
        $msg = ContactMessage::first();
        $this->assertEquals('new', $msg->status->value);
        $this->assertNull($msg->read_at);
        $this->assertNull($msg->archived_at);
    }

    public function test_nul_byte_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => "John \0 Doe",
            'contact_type' => 'email',
            'contact_value' => 'john@example.com',
            'subject' => 'Pertanyaan',
            'message' => 'Valid message length 10+',
            'cf-turnstile-response' => 'valid',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_control_character_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'contact_type' => 'email',
            'contact_value' => 'john@example.com',
            'subject' => "Pertanyaan \x07",
            'message' => 'Valid message length 10+',
            'cf-turnstile-response' => 'valid',
        ]);

        $response->assertSessionHasErrors(['subject']);
    }

    public function test_no_public_listing_or_detail_route()
    {
        $this->get('/contact-messages')->assertStatus(404);
        $this->get('/kontak/1')->assertStatus(404);
    }
}