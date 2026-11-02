<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\FossilSource;
use App\Models\Fossil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FossilApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_fossils_in_the_fractal_response_format(): void
    {
        Fossil::factory()->create([
            'source' => FossilSource::Naturalis,
            'external_id' => 'NAT-API-001',
            'scientific_name' => 'Ammonite example',
            'latitude' => 52.1,
            'longitude' => 5.1,
        ]);

        $this->getJson('/api/fossils')
            ->assertOk()
            ->assertJsonPath('data.0.source', 'naturalis')
            ->assertJsonPath('data.0.external_id', 'NAT-API-001')
            ->assertJsonPath('data.0.scientific_name', 'Ammonite example')
            ->assertJsonPath('data.0.latitude', 52.1)
            ->assertJsonPath('data.0.longitude', 5.1)
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_it_filters_fossils_by_source_and_scientific_name(): void
    {
        Fossil::factory()->create([
            'source' => FossilSource::Naturalis,
            'scientific_name' => 'Ammonite example',
        ]);
        Fossil::factory()->create([
            'source' => FossilSource::Gbif,
            'scientific_name' => 'Trilobite example',
        ]);

        $this->getJson('/api/fossils?source=gbif&scientific_name=Trilobite&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'gbif')
            ->assertJsonPath('data.0.scientific_name', 'Trilobite example')
            ->assertJsonPath('meta.pagination.per_page', 1);
    }

    public function test_it_validates_source_and_pagination_query_parameters(): void
    {
        foreach ([
            'source=other',
            'per_page=0',
            'per_page=101',
            'scientific_name%5B%5D=ammonite',
        ] as $query) {
            $this->getJson('/api/fossils?'.$query)->assertUnprocessable();
        }
    }

    public function test_it_returns_one_fossil_by_uuid(): void
    {
        $fossil = Fossil::factory()->create([
            'source' => FossilSource::Gbif,
            'external_id' => 'GBIF-API-001',
            'scientific_name' => 'Trilobite example',
        ]);

        $this->getJson('/api/fossils/'.$fossil->id)
            ->assertOk()
            ->assertJsonPath('data.id', $fossil->id)
            ->assertJsonPath('data.source', 'gbif')
            ->assertJsonPath('data.external_id', 'GBIF-API-001');
    }

    public function test_it_returns_not_found_for_an_unknown_fossil_uuid(): void
    {
        $this->getJson('/api/fossils/018f47e2-8d35-7e8d-a5a2-f858b1b8d5c1')->assertNotFound();
    }
}
