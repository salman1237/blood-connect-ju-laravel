<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyRequestsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_requests_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/requests/mine');

        $response->assertOk();
    }

    public function test_my_requests_page_shows_only_the_users_own_posted_requests(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $ownRequest = BloodRequest::factory()->for($user, 'requester')->create(['hospital_name' => 'Enam Medical College Hospital']);
        $someoneElse = User::factory()->create(['role' => 'staff']);
        BloodRequest::factory()->for($someoneElse, 'requester')->create();

        $response = $this->actingAs($user)->get('/requests/mine');

        $response->assertOk();
        $myRequests = $response->viewData('myRequests');
        $this->assertCount(1, $myRequests);
        $this->assertSame($ownRequest->id, $myRequests->first()->id);
        $response->assertSee('Enam Medical College Hospital');
    }

    public function test_my_requests_page_shows_an_empty_state_when_no_requests_posted(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user)->get('/requests/mine');

        $response->assertOk();
        $this->assertCount(0, $response->viewData('myRequests'));
        $response->assertSee('posted a request yet');
    }
}
