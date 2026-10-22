<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page sends visitors to the bank dashboard
     * (which in turn sends guests to the login page).
     */
    public function test_home_page_redirects_to_the_bank_dashboard(): void
    {
        $this->get('/')->assertRedirect('/bank');
    }
}
