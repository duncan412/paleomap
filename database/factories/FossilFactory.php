<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FossilSource;
use App\Models\Fossil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fossil>
 */
final class FossilFactory extends Factory
{
    private static int $sequence = 0;

    protected $model = Fossil::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'id' => sprintf('00000000-0000-4000-8000-%012d', $sequence),
            'source' => FossilSource::Naturalis,
            'external_id' => sprintf('NAT-%05d', $sequence),
            'scientific_name' => 'Ammonite example',
            'geological_context' => 'Cretaceous',
            'latitude' => 52.1,
            'longitude' => 5.1,
            'raw_json' => [],
        ];
    }
}
