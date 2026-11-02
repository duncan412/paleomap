<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FossilOccurrence;
use App\Enums\FossilSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

final class GbifApiService
{
    private const string ENDPOINT = 'https://api.gbif.org/v1/occurrence/search';

    public function __construct(
        private readonly SpatialService $spatialService,
    ) {}

    /**
     * @return list<FossilOccurrence>
     */
    public function fetch(): array
    {
        $response = Http::acceptJson()
            ->timeout(30)
            ->get(self::ENDPOINT, ['basisOfRecord' => 'FOSSIL_SPECIMEN', 'limit' => 50])
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new UnexpectedValueException('GBIF API response must be a JSON object.');
        }

        $records = $response['results'] ?? null;
        if (! is_array($records) || ! array_is_list($records)) {
            throw new UnexpectedValueException('GBIF API response must contain a results list.');
        }

        $occurrences = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                Log::warning('Skipping malformed GBIF fossil record.');

                continue;
            }

            /** @var array<string, mixed> $record */
            $occurrence = $this->mapRecord($record);
            if ($occurrence !== null) {
                $occurrences[] = $occurrence;
            }
        }

        return $occurrences;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function mapRecord(array $record): ?FossilOccurrence
    {
        $externalId = $this->stringValue($record['gbifID'] ?? null);
        $scientificName = $this->stringValue($record['scientificName'] ?? null);

        if ($externalId === null || $scientificName === null) {
            Log::warning('Skipping GBIF fossil record without an identifier or scientific name.');

            return null;
        }

        $latitude = $this->floatValue($record['decimalLatitude'] ?? null);
        $longitude = $this->floatValue($record['decimalLongitude'] ?? null);
        $point = $this->spatialService->createPointFromCoords($latitude, $longitude);
        $region = $this->stringValue($record['stateProvince'] ?? null);
        $country = $this->stringValue($record['country'] ?? null);
        $geologicalContext = implode(', ', array_filter(
            [$region, $country],
            static fn (?string $value): bool => $value !== null,
        ));

        return new FossilOccurrence(
            FossilSource::Gbif,
            $externalId,
            $scientificName,
            $geologicalContext === '' ? null : $geologicalContext,
            $point?->y(),
            $point?->x(),
            $record,
        );
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function floatValue(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
