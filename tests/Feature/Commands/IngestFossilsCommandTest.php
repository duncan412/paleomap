<?php

declare(strict_types=1);

use App\Enums\FossilSource;
use App\Models\Fossil;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

it('ingests Naturalis fossil records', function (): void {
    Http::fake([
        'api.biodiversitydata.nl/*' => Http::response([
            'items' => [[
                'unitID' => 'NAT-001',
                'identifications' => [[
                    'scientificName' => ['fullScientificName' => 'Ammonite example'],
                ]],
                'gatheringEvent' => [
                    'siteCoordinates' => ['lat' => 52.1, 'lon' => 5.1],
                    'geologicalContext' => 'Cretaceous',
                ],
            ]],
        ]),
    ]);

    expect(Artisan::call('fossils:ingest', ['--source' => 'naturalis']))->toBe(Command::SUCCESS);

    expect(DB::table('fossils')->where([
        'source' => FossilSource::Naturalis->value,
        'external_id' => 'NAT-001',
        'scientific_name' => 'Ammonite example',
        'geological_context' => 'Cretaceous',
        'latitude' => 52.1,
        'longitude' => 5.1,
    ])->exists())->toBeTrue();
    expect(DB::table('fossils')->where('external_id', 'NAT-001')->value('location'))->not->toBeNull();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://api.biodiversitydata.nl/v2/specimen/search/')
        && $request['limit'] === 50
    );
    Http::assertSentCount(1);
});

it('ingests GBIF fossil records', function (): void {
    Http::fake([
        'api.gbif.org/*' => Http::response([
            'results' => [[
                'gbifID' => 'GBIF-001',
                'scientificName' => 'Trilobite example',
                'decimalLatitude' => 40.2,
                'decimalLongitude' => -70.3,
                'stateProvince' => 'Massachusetts',
                'country' => 'United States',
            ]],
        ]),
    ]);

    expect(Artisan::call('fossils:ingest', ['--source' => 'gbif']))->toBe(Command::SUCCESS);

    expect(DB::table('fossils')->where([
        'source' => FossilSource::Gbif->value,
        'external_id' => 'GBIF-001',
        'scientific_name' => 'Trilobite example',
        'geological_context' => 'Massachusetts, United States',
        'latitude' => 40.2,
        'longitude' => -70.3,
    ])->exists())->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://api.gbif.org/v1/occurrence/search')
        && str_contains($request->url(), 'basisOfRecord=FOSSIL_SPECIMEN')
        && str_contains($request->url(), 'limit=50')
    );
    Http::assertSentCount(1);
});

it('ingests both sources and preserves each raw API record', function (): void {
    Http::fake([
        'api.biodiversitydata.nl/*' => Http::response([
            'items' => [[
                'unitID' => 'NAT-002',
                'identifications' => [[
                    'scientificName' => ['fullScientificName' => 'Naturalis specimen'],
                ]],
                'gatheringEvent' => ['siteCoordinates' => ['lat' => 10, 'lon' => 20]],
            ]],
        ]),
        'api.gbif.org/*' => Http::response([
            'results' => [[
                'gbifID' => 'GBIF-002',
                'scientificName' => 'GBIF specimen',
                'decimalLatitude' => null,
                'decimalLongitude' => null,
                'country' => 'France',
            ]],
        ]),
    ]);

    expect(Artisan::call('fossils:ingest'))->toBe(Command::SUCCESS);

    $naturalis = Fossil::query()->where('external_id', 'NAT-002')->firstOrFail();
    $gbif = Fossil::query()->where('external_id', 'GBIF-002')->firstOrFail();
    expect(Fossil::query()->count())->toBe(2)
        ->and($naturalis->raw_json['unitID'])->toBe('NAT-002')
        ->and($gbif->latitude)->toBeNull();

    Http::assertSentCount(2);
});

it('updates existing records instead of inserting duplicates', function (): void {
    Http::fake([
        'api.gbif.org/*' => Http::response([
            'results' => [[
                'gbifID' => 'GBIF-003',
                'scientificName' => 'Updated fossil name',
                'decimalLatitude' => 0,
                'decimalLongitude' => 0,
            ]],
        ]),
    ]);

    expect(Artisan::call('fossils:ingest', ['--source' => 'gbif']))->toBe(Command::SUCCESS)
        ->and(Artisan::call('fossils:ingest', ['--source' => 'gbif']))->toBe(Command::SUCCESS);

    expect(Fossil::query()->where('external_id', 'GBIF-003')->count())->toBe(1)
        ->and(Fossil::query()->where('external_id', 'GBIF-003')->value('scientific_name'))
        ->toBe('Updated fossil name');

    Http::assertSentCount(2);
});

it('rejects unknown source values without making requests', function (): void {
    Http::fake();

    expect(Artisan::call('fossils:ingest', ['--source' => 'unknown']))->toBe(Command::FAILURE);

    Http::assertNothingSent();
});
