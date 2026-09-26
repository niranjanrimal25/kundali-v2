<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path is a redirect into the app, which in turn requires
     * authentication — so a guest ends up at the login screen.
     */
    public function test_the_root_path_redirects_guests_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
