<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * A singleton row -- one record ever -- holding the org credit shown on the
 * landing page, the Settings page, and Android's Settings screen (via
 * MetaController). Read on nearly every page load, so current() is cached;
 * every write goes through the model (never a raw query), so the cache
 * always gets busted alongside it.
 */
class AppSetting extends Model
{
    protected $fillable = [
        'funded_by',
        'maintained_by',
        'logo_url',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('app_setting'));
        static::deleted(fn () => Cache::forget('app_setting'));
    }

    public static function current(): self
    {
        return Cache::rememberForever('app_setting', fn () => static::query()->firstOrCreate([], []));
    }

    /**
     * Mirrors User::updateAvatar() -- delete whatever was there before so
     * replaced uploads don't pile up in storage, then store the new one.
     */
    public function updateLogo(UploadedFile $file): void
    {
        $this->deleteStoredLogoIfLocal();

        $path = $file->store('org', 'public');

        $this->update(['logo_url' => Storage::disk('public')->url($path)]);
    }

    public function removeLogo(): void
    {
        $this->deleteStoredLogoIfLocal();

        $this->update(['logo_url' => null]);
    }

    private function deleteStoredLogoIfLocal(): void
    {
        $publicBaseUrl = Storage::disk('public')->url('');

        if ($this->logo_url && str_starts_with($this->logo_url, $publicBaseUrl)) {
            Storage::disk('public')->delete(str_replace($publicBaseUrl, '', $this->logo_url));
        }
    }
}
