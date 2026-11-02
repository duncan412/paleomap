<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FossilSource;
use Database\Factories\FossilFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property FossilSource $source
 * @property string $external_id
 * @property string $scientific_name
 * @property string|null $geological_context
 * @property numeric-string|null $latitude
 * @property numeric-string|null $longitude
 * @property array<string, mixed> $raw_json
 */
final class Fossil extends Model
{
    /** @use HasFactory<FossilFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source',
        'external_id',
        'scientific_name',
        'geological_context',
        'latitude',
        'longitude',
        'location',
        'raw_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => FossilSource::class,
            'raw_json' => 'array',
        ];
    }
}
