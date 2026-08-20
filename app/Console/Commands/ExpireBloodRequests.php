<?php

namespace App\Console\Commands;

use App\Models\BloodRequest;
use Illuminate\Console\Command;

class ExpireBloodRequests extends Command
{
    protected $signature = 'requests:expire';

    protected $description = 'Auto-expire open blood requests past their expiry time';

    public function handle(): int
    {
        $count = BloodRequest::open()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} stale request(s).");

        return self::SUCCESS;
    }
}
