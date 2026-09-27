<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use App\Support\ContentSecurity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ContentSecurityTest extends TestCase
{
    public function test_rich_text_preserves_formatting_and_removes_active_content(): void
    {
        $html = ContentSecurity::richText('<h2>Judul</h2><p><strong>Tebal</strong> <em>Miring</em></p><ul><li>Item</li></ul><table><tr><td>Nilai</td></tr></table><img src="/storage/photo.png" onerror=alert(1)><a href="jav&#x61;script:alert(1)">link</a><script>alert(1)</script>');
        $this->assertStringContainsString('<h2>Judul</h2>', $html);
        $this->assertStringContainsString('<strong>Tebal</strong>', $html);
        $this->assertStringContainsString('<li>Item</li>', $html);
        $this->assertStringContainsString('<td>Nilai</td>', $html);
        $this->assertStringContainsString('/storage/photo.png', $html);
        foreach (['onerror', 'javascript:', '<script'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, strtolower($html));
        }
    }

    public function test_url_policy_preserves_local_links_and_rejects_script_schemes(): void
    {
        foreach (['/halaman/profil', '#profil-desa', 'https://example.test/?a=1&b=2'] as $url) {
            $this->assertSame($url, ContentSecurity::url($url));
        }
        foreach (["java\tscript:alert(1)", 'javascript:alert(1)', 'data:text/html,test', '//example.test', '/\\example.test', 'https://example.test/" onclick="bad'] as $url) {
            $this->assertSame('#', ContentSecurity::url($url));
        }
    }

    public function test_builder_preview_and_public_component_share_rich_text_policy(): void
    {
        $content = '<h2>Safe heading</h2><p><em>Formatting</em></p><img src="/photo.png" onerror=alert(1)>';
        $preview = \App\Filament\Support\PreviewStateNormalizer::pageComponent('rich_text', ['content' => $content]);
        $this->assertSame(ContentSecurity::richText($content), $preview['content']);
        $html = view('pages.components.rich_text', ['data' => ['content' => $content]])->render();
        $this->assertStringContainsString('<h2>Safe heading</h2>', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_all_response_types_use_header_bag_and_keep_enforcing_csp(): void
    {
        foreach ([new Response('ok'), new StreamedResponse(fn () => null), new BinaryFileResponse(__FILE__)] as $response) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
            $result = app(SecurityHeaders::class)->apply(Request::create('/'), $response);
            $this->assertSame('nosniff', $result->headers->get('X-Content-Type-Options'));
            $this->assertSame('SAMEORIGIN', $result->headers->get('X-Frame-Options'));
            $this->assertSame("frame-ancestors 'self'", $result->headers->get('Content-Security-Policy'));
            $this->assertNotNull($result->headers->get('Content-Security-Policy-Report-Only'));
        }
    }
}
