<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_public_landing_page_welcomes_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('Crewly');
    }

    public function test_guests_are_redirected_to_login_from_the_app(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
