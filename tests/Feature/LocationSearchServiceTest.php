<?php

use App\Exceptions\LocationSearchException;
use App\Services\LocationSearchService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
});

test('location search normalizes and caches bounded Indonesian Nominatim results', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            [
                'display_name' => 'Kantor Desa Kertajaya, Sukabumi, Jawa Barat',
                'lat' => '-6.987654321',
                'lon' => '106.123456789',
            ],
        ]),
    ]);

    $service = app(LocationSearchService::class);
    $first = $service->search('  Kantor   Desa Kertajaya  ');
    $second = $service->search('Kantor Desa Kertajaya');

    expect($first)->toBe($second)
        ->and($first)->toBe([[
            'display_name' => 'Kantor Desa Kertajaya, Sukabumi, Jawa Barat',
            'latitude' => '-6.9876543',
            'longitude' => '106.1234568',
        ]]);

    Http::assertSentCount(1);
    Http::assertSent(function ($request): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/search?')
            && $query['q'] === 'Kantor Desa Kertajaya'
            && $query['format'] === 'jsonv2'
            && $query['addressdetails'] === '1'
            && $query['countrycodes'] === 'id'
            && $query['limit'] === '5'
            && $request->hasHeader('User-Agent');
    });
});

test('location search returns an empty result set without inventing a selection', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    expect(app(LocationSearchService::class)->search('Lokasi yang tidak ada'))->toBe([]);
});

test('location search rejects empty short oversized and URL inputs before outbound traffic', function (string $query) {
    Http::fake();

    expect(fn () => app(LocationSearchService::class)->search($query))
        ->toThrow(ValidationException::class);

    Http::assertNothingSent();
})->with([
    'empty' => '',
    'short' => 'ab',
    'oversized' => str_repeat('a', 161),
    'URL' => 'https://example.test/location',
]);

test('location search fails safely on an upstream error or malformed response', function (string $query, mixed $body, int $status) {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response($body, $status)]);

    expect(fn () => app(LocationSearchService::class)->search($query))
        ->toThrow(LocationSearchException::class);
})->with([
    'upstream error' => ['Kantor Kecamatan Kertajaya', ['error' => 'busy'], 503],
    'malformed response' => ['Balai Warga Kertajaya', ['unexpected' => 'shape'], 200],
]);

test('location search fails safely on a connection timeout', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(fn () => app(LocationSearchService::class)->search('Puskesmas Kertajaya'))
        ->toThrow(LocationSearchException::class);
});

test('location search rate limits distinct uncached upstream requests', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);
    $service = app(LocationSearchService::class);

    expect($service->search('Kantor Desa Kertajaya'))->toBe([])
        ->and(fn () => $service->search('Balai Desa Kertajaya'))
        ->toThrow(LocationSearchException::class);

    Http::assertSentCount(1);
});
