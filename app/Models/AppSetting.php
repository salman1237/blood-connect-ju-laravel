<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * A singleton row -- one record ever -- holding the org credit shown on the
 * landing page, the Settings page, and Android's Settings screen (via
 * MetaController). Read on nearly every page load, so current() is cached;
 * every write goes through the model (never a raw query), so the cache
 * always gets busted alongside it.
 */
class AppSetting extends Model
{
    /**
     * The two logo slots -- one per credit line (JUCSU's own logo next to
     * "funded by", Badhan's own logo next to "maintained by"), not one
     * shared logo for both.
     */
    private const LOGO_COLUMNS = [
        'funded_by' => 'funded_by_logo_url',
        'maintained_by' => 'maintained_by_logo_url',
    ];

    protected $fillable = [
        'funded_by',
        'maintained_by',
        'funded_by_logo_url',
        'maintained_by_logo_url',
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
     * $which is 'funded_by' or 'maintained_by', matching the route segment
     * AdminSettingsController passes straight through.
     */
    public function updateLogo(string $which, UploadedFile $file): void
    {
        $column = $this->logoColumn($which);

        $this->deleteStoredFileIfLocal($this->$column);

        $path = $file->store('org', 'public');

        $this->update([$column => Storage::disk('public')->url($path)]);
    }

    public function removeLogo(string $which): void
    {
        $column = $this->logoColumn($which);

        $this->deleteStoredFileIfLocal($this->$column);

        $this->update([$column => null]);
    }

    private function logoColumn(string $which): string
    {
        return self::LOGO_COLUMNS[$which] ?? throw new InvalidArgumentException("Unknown logo slot: {$which}");
    }

    private function deleteStoredFileIfLocal(?string $url): void
    {
        $publicBaseUrl = Storage::disk('public')->url('');

        if ($url && str_starts_with($url, $publicBaseUrl)) {
            Storage::disk('public')->delete(str_replace($publicBaseUrl, '', $url));
        }
    }
}
