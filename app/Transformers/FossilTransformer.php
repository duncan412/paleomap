<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Fossil;
use League\Fractal\TransformerAbstract;

final class FossilTransformer extends TransformerAbstract
{
    /**
     * @return array{
     *     id: string,
     *     source: string,
     *     external_id: string,
     *     scientific_name: string,
     *     geological_context: string|null,
     *     latitude: float|null,
     *     longitude: float|null
     * }
     */
    public function transform(Fossil $fossil): array
    {
        return [
            'id' => $fossil->id,
            'source' => $fossil->source->value,
            'external_id' => $fossil->external_id,
            'scientific_name' => $fossil->scientific_name,
            'geological_context' => $fossil->geological_context,
            'latitude' => $fossil->latitude === null ? null : (float) $fossil->latitude,
            'longitude' => $fossil->longitude === null ? null : (float) $fossil->longitude,
        ];
    }
}
