<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The application boots and the root route answers.
     *
     * With no edition seeded the home controller renders the "unavailable" page,
     * so this asserts the app starts and a request completes end to end on an
     * empty database. It is deliberately the cheapest possible smoke test;
     * the home page's real content is covered by PublicPagesTest.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
