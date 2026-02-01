<?php

namespace Routina\Services;

/**
 * Public holiday lookup with lightweight caching.
 * Uses Nager.Date public API.
 */
class HolidayService
{
    private const BASE_URL = 'https://date.nager.at/api/v3';
    private const CACHE_TTL_SECONDS = 2592000; // 30 days

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getPublicHolidays(string $countryCode, int $year): array
    {
        $countryCode = strtoupper(trim($countryCode));
        if ($countryCode === '' || !preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return [];
        }

        $cacheFile = self::cachePath($countryCode, $year);
        $cached = self::readCache($cacheFile);
        if ($cached !== null) {
            return $cached;
        }

        $url = self::BASE_URL . '/PublicHolidays/' . $year . '/' . $countryCode;
        $payload = self::fetchJson($url);
        if (!is_array($payload)) {
            return [];
        }

        self::writeCache($cacheFile, $payload);
        return $payload;
    }

    /**
     * Provide a small curated list for the profile picker.
     *
     * @return array<string, string>
     */
    public static function commonCountries(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'IN' => 'India',
            'AE' => 'United Arab Emirates',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'SG' => 'Singapore',
            'MY' => 'Malaysia',
            'ZA' => 'South Africa',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands'
        ];
    }

    private static function cachePath(string $countryCode, int $year): string
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'holidays' . DIRECTORY_SEPARATOR . $countryCode;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir . DIRECTORY_SEPARATOR . $year . '.json';
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function readCache(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        $mtime = @filemtime($path);
        if ($mtime && (time() - $mtime) > self::CACHE_TTL_SECONDS) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function writeCache(string $path, array $data): void
    {
        @file_put_contents($path, json_encode($data));
    }

    /**
     * @return array<string, mixed>|array<int, mixed>|null
     */
    private static function fetchJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 6,
                'header' => "User-Agent: Routina/1.0\r\n"
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
