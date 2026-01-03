<?php

namespace Tests\Feature;

use Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_dashboard_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Bondig');
    }

    public function test_upload_page_loads(): void
    {
        $response = $this->get('/upload');

        $response->assertStatus(200);
        $response->assertSee('Upload Receipt');
    }

    public function test_products_page_loads(): void
    {
        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Products');
    }

    public function test_navigation_links_present_on_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Upload');
        $response->assertSee('Products');
    }

    public function test_bondig_theme_is_applied(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Verify daisyUI bondig theme is set on HTML element
        $response->assertSee('data-theme="bondig"', false);
    }

    public function test_daisyui_navbar_component_renders(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Verify daisyUI navbar component classes are present
        $response->assertSee('navbar', false);
        $response->assertSee('navbar-start', false);
        $response->assertSee('navbar-center', false);
    }

    public function test_primary_color_applied_to_brand(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Verify brand uses text-primary class (teal color)
        $response->assertSee('text-primary', false);
    }

    public function test_stone_background_applied(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Verify stone background via daisyUI base-200 class
        $response->assertSee('bg-base-200', false);
    }

    public function test_responsive_mobile_menu_present(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Verify responsive hamburger menu is present (hidden on desktop, visible on mobile)
        $response->assertSee('lg:hidden', false);
        $response->assertSee('dropdown', false);
    }
}
