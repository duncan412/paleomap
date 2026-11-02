<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\FossilOccurrence;
use App\Enums\FossilSource;
use App\Models\Fossil;
use App\Services\GbifApiService;
use App\Services\NaturalisApiService;
use App\Services\SpatialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class IngestFossilsCommand extends Command
{
    protected $signature = 'fossils:ingest {--source=all : Data source to ingest (naturalis, gbif, or all)}';

    protected $description = 'Ingest fossil specimen records from Naturalis and GBIF.';

    public function handle(
        NaturalisApiService $naturalisApi,
        GbifApiService $gbifApi,
        SpatialService $spatialService,
    ): int {
        $source = $this->option('source');
        if (! is_string($source)) {
            $this->error('The --source option must be a string.');

            return self::FAILURE;
        }

        $occurrences = match ($source) {
            FossilSource::Naturalis->value => $naturalisApi->fetch(),
            FossilSource::Gbif->value => $gbifApi->fetch(),
            'all' => [...$naturalisApi->fetch(), ...$gbifApi->fetch()],
            default => null,
        };

        if ($occurrences === null) {
            $this->error('Invalid source. Use naturalis, gbif, or all.');

            return self::FAILURE;
        }

        $saved = 0;
        foreach ($occurrences as $occurrence) {
            $this->storeOccurrence($occurrence, $spatialService);
            $saved++;
        }

        $this->info(sprintf('Ingested %d fossil record(s).', $saved));

        return self::SUCCESS;
    }

    private function storeOccurrence(FossilOccurrence $occurrence, SpatialService $spatialService): void
    {
        $point = $spatialService->createPointFromCoords($occurrence->latitude, $occurrence->longitude);
        $wellKnownText = $point?->asText();
        $location = match (DB::connection()->getDriverName()) {
            'pgsql', 'mysql', 'mariadb' => $wellKnownText === null
                ? null
                : DB::raw(sprintf("ST_GeomFromText('%s', 4326)", $wellKnownText)),
            default => $wellKnownText,
        };

        Fossil::query()->updateOrCreate(
            [
                'source' => $occurrence->source,
                'external_id' => $occurrence->externalId,
            ],
            [
                'scientific_name' => $occurrence->scientificName,
                'geological_context' => $occurrence->geologicalContext,
                'latitude' => $occurrence->latitude,
                'longitude' => $occurrence->longitude,
                'location' => $location,
                'raw_json' => $occurrence->rawJson,
            ],
        );
    }
}
