<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\User;

use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


class ExampleTest extends TestCase
{

    use RefreshDatabase;



    public function test_api_is_available(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $response = $this->getJson(
            '/api/v1/reports/summary'
        );


        $response->assertStatus(200);

    }

}