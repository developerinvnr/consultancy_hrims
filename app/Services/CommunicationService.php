<?php
// app/Services/CommunicationService.php

namespace App\Services;

use App\Models\CommunicationControl;
use Illuminate\Support\Facades\Cache;

class CommunicationService
{
    const CACHE_KEY_PREFIX = 'communication_control_';
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Check if a communication type is enabled
     */
    public function isEnabled(string $key): bool
    {
        return Cache::remember(
            self::CACHE_KEY_PREFIX . $key,
            self::CACHE_DURATION,
            function () use ($key) {
                $control = CommunicationControl::where('control_key', $key)->first();
                return $control ? $control->is_active : false;
            }
        );
    }

    /**
     * Clear cache for a specific control
     */
    public function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $key);
    }
}