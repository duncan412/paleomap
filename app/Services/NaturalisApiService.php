<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FossilOccurrence;
use App\Enums\FossilSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

final class NaturalisApiService
{
    private const string ENDPOINT = 'https://api.biodiversitydata.nl/v2/specimen/search/';

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
            ->get(self::ENDPOINT, ['limit' => 50])
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new UnexpectedValueException('Naturalis API response must be a JSON object.');
        }

        $records = $response['items'] ?? null;
        if (! is_array($records) || ! array_is_list($records)) {
            throw new UnexpectedValueException('Naturalis API response must contain an items list.');
        }

        $occurrences = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                Log::warning('Skipping malformed Naturalis fossil record.');

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
        $externalId = $this->stringValue($record['unitID'] ?? null);
        $identifications = $record['identifications'] ?? null;
        $firstIdentification = is_array($identifications) ? ($identifications[0] ?? null) : null;
        $scientificNameData = is_array($firstIdentification)
            ? ($firstIdentification['scientificName'] ?? null)
            : null;
        $scientificName = is_array($scientificNameData)
            ? $this->stringValue($scientificNameData['fullScientificName'] ?? null)
            : null;

        if ($externalId === null || $scientificName === null) {
            Log::warning('Skipping Naturalis fossil record without an identifier or scientific name.');

            return null;
        }

        $gatheringEvent = $record['gatheringEvent'] ?? null;
        $coordinates = is_array($gatheringEvent) ? ($gatheringEvent['siteCoordinates'] ?? null) : null;
        $latitude = is_array($coordinates) ? $this->floatValue($coordinates['lat'] ?? null) : null;
        $longitude = is_array($coordinates) ? $this->floatValue($coordinates['lon'] ?? null) : null;
        $point = $this->spatialService->createPointFromCoords($latitude, $longitude);

        $geologicalContext = is_array($gatheringEvent)
            ? $this->stringValue($gatheringEvent['geologicalContext'] ?? null)
            : null;

        return new FossilOccurrence(
            FossilSource::Naturalis,
            $externalId,
            $scientificName,
            $geologicalContext,
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
