<?php

namespace App\Services;

use App\Exceptions\LocationSearchException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LocationSearchService
{
    public const RESULT_LIMIT = 5;

    public const CACHE_TTL_SECONDS = 86400;

    public const TIMEOUT_SECONDS = 5;

    public const RATE_LIMIT_ATTEMPTS = 1;

    public const RATE_LIMIT_DECAY_SECONDS = 1;

    private const SEARCH_URL = 'https://nominatim.openstreetmap.org/search';

    /**
     * @return array<int, array{display_name: string, latitude: string, longitude: string}>
     */
    public function search(string $query): array
    {
        $query = $this->normalizeQuery($query);
        $this->validateQuery($query);

        $cacheKey = 'location-search:nominatim:'.hash('sha256', mb_strtolower($query));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($query): array {
            $rateLimitKey = 'location-search:'.hash('sha256', implode('|', [
                (string) (auth()->id() ?? 'guest'),
                (string) (request()->ip() ?? 'unknown'),
            ]));

            if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_ATTEMPTS)) {
                throw new LocationSearchException('Terlalu banyak pencarian lokasi. Tunggu sebentar lalu coba lagi.');
            }

            RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY_SECONDS);

            try {
                $response = Http::acceptJson()
                    ->withUserAgent($this->userAgent())
                    ->connectTimeout(2)
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->get(self::SEARCH_URL, [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'countrycodes' => 'id',
                        'limit' => self::RESULT_LIMIT,
                    ]);
            } catch (ConnectionException) {
                throw new LocationSearchException('Layanan pencarian lokasi tidak dapat dihubungi. Gunakan koordinat manual atau coba lagi nanti.');
            }

            if (! $response->successful()) {
                throw new LocationSearchException('Layanan pencarian lokasi sedang tidak tersedia. Gunakan koordinat manual atau coba lagi nanti.');
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new LocationSearchException('Respons pencarian lokasi tidak valid. Gunakan koordinat manual atau coba lagi nanti.');
            }

            $results = [];
            foreach (array_slice($payload, 0, self::RESULT_LIMIT) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $displayName = trim((string) ($item['display_name'] ?? ''));
                $latitude = $item['lat'] ?? null;
                $longitude = $item['lon'] ?? null;

                if ($displayName === '' || ! is_numeric($latitude) || ! is_numeric($longitude)) {
                    continue;
                }

                $latitude = (float) $latitude;
                $longitude = (float) $longitude;
                if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                    continue;
                }

                $results[] = [
                    'display_name' => Str::limit(strip_tags($displayName), 500, ''),
                    'latitude' => number_format($latitude, 7, '.', ''),
                    'longitude' => number_format($longitude, 7, '.', ''),
                ];
            }

            if ($payload !== [] && $results === []) {
                throw new LocationSearchException('Respons pencarian lokasi tidak valid. Gunakan koordinat manual atau coba lagi nanti.');
            }

            return $results;
        });
    }

    private function normalizeQuery(string $query): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $query));
    }

    private function validateQuery(string $query): void
    {
        Validator::make(
            ['query' => $query],
            [
                'query' => [
                    'required',
                    'string',
                    'min:3',
                    'max:160',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', (string) $value)) {
                            $fail('Masukkan nama tempat atau alamat, bukan URL.');
                        }
                    },
                ],
            ],
            [
                'query.required' => 'Masukkan nama tempat atau alamat.',
                'query.min' => 'Pencarian lokasi minimal 3 karakter.',
                'query.max' => 'Pencarian lokasi maksimal 160 karakter.',
            ],
        )->validate();
    }

    private function userAgent(): string
    {
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '', (string) config('app.name', 'Village CMS'));
        $url = (string) config('app.url', '');

        return trim("{$name}/1.0 ({$url})");
    }
}
