<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_age_is_null_without_a_date_of_birth(): void
    {
        $user = new User(['date_of_birth' => null]);

        $this->assertNull($user->age);
    }

    public function test_age_is_computed_from_date_of_birth(): void
    {
        $user = new User(['date_of_birth' => Carbon::now()->subYears(25)->subDays(1)]);

        $this->assertSame(25, $user->age);
    }

    public function test_whatsapp_url_is_null_without_any_number(): void
    {
        $user = new User(['phone_has_whatsapp' => true, 'phone' => null, 'whatsapp_number' => null]);

        $this->assertNull($user->whatsapp_url);
    }

    public function test_whatsapp_url_uses_phone_when_it_has_whatsapp(): void
    {
        $user = new User(['phone_has_whatsapp' => true, 'phone' => '01712345678', 'whatsapp_number' => null]);

        $this->assertSame('https://wa.me/8801712345678', $user->whatsapp_url);
    }

    public function test_whatsapp_url_uses_alternate_number_when_phone_has_no_whatsapp(): void
    {
        $user = new User(['phone_has_whatsapp' => false, 'phone' => '01712345678', 'whatsapp_number' => '01899999999']);

        $this->assertSame('https://wa.me/8801899999999', $user->whatsapp_url);
    }

    public function test_whatsapp_url_is_null_when_phone_has_no_whatsapp_and_no_alternate_given(): void
    {
        $user = new User(['phone_has_whatsapp' => false, 'phone' => '01712345678', 'whatsapp_number' => null]);

        $this->assertNull($user->whatsapp_url);
    }

    public function test_whatsapp_url_handles_a_number_already_in_international_format(): void
    {
        $user = new User(['phone_has_whatsapp' => true, 'phone' => '+880 1712-345678', 'whatsapp_number' => null]);

        $this->assertSame('https://wa.me/8801712345678', $user->whatsapp_url);
    }
}
