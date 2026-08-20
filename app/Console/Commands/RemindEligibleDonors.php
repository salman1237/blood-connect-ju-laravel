<?php

namespace App\Console\Commands;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Notifications\EligibleDonorReminder;
use Illuminate\Console\Command;

class RemindEligibleDonors extends Command
{
    protected $signature = 'donors:remind-eligible';

    protected $description = "Email eligible donors who haven't donated in a while (or ever) and aren't in their reminder cooldown";

    public function handle(): int
    {
        // For each donor blood group, how many currently-open requests they
        // could give blood for — computed once via the compatibility
        // matrix rather than per-donor, so the reminder can say "N open
        // requests match you" without an N+1 query per recipient.
        $openCountsByRequestGroup = BloodRequest::open()
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        $matchingCountsByDonorGroup = [];
        foreach (BloodRequest::DONOR_COMPATIBILITY as $requestGroup => $compatibleDonorGroups) {
            $count = $openCountsByRequestGroup[$requestGroup] ?? 0;
            if ($count === 0) {
                continue;
            }
            foreach ($compatibleDonorGroups as $donorGroup) {
                $matchingCountsByDonorGroup[$donorGroup] = ($matchingCountsByDonorGroup[$donorGroup] ?? 0) + $count;
            }
        }

        $donors = DonorProfile::query()->dueForReminder()->with('user')->get();

        foreach ($donors as $profile) {
            $profile->user->notify(new EligibleDonorReminder($matchingCountsByDonorGroup[$profile->blood_group] ?? 0));
            $profile->update(['last_reminded_at' => now()]);
        }

        $this->info("Reminded {$donors->count()} eligible donor(s).");

        return self::SUCCESS;
    }
}
