<?php

namespace Tests\Feature;

use Tests\TestCase;

class HttpRoutesVerificationTest extends TestCase
{
    public function test_public_routes_return_200()
    {
        $this->get('/')->assertStatus(200);
        $this->get('/berita')->assertStatus(200);
        $this->get('/galeri')->assertStatus(200);
        $this->get('/dokumen')->assertStatus(200);
    }

    public function test_non_existent_route_returns_404()
    {
        $this->get('/this-route-does-not-exist-12345')->assertStatus(404);
    }

    public function test_unauthenticated_admin_dashboard_redirects_to_login()
    {
        $response = $this->get('/test-admin-path');
        $response->assertRedirect('/test-admin-path/login');
    }
}
