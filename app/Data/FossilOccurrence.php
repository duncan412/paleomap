<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FossilSource;

final readonly class FossilOccurrence
{
    /**
     * @param  array<string, mixed>  $rawJson
     */
    public function __construct(
        public FossilSource $source,
        public string $externalId,
        public string $scientificName,
        public ?string $geologicalContext,
        public ?float $latitude,
        public ?float $longitude,
        public array $rawJson,
    ) {}
}
