<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\FossilSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class FossilIndexRequest extends FormRequest
{
    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [];
    }

    /**
     * @return array<string, array<int, string|Enum>>
     */
    public function rules(): array
    {
        return [
            'source' => ['sometimes', Rule::enum(FossilSource::class)],
            'scientific_name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function source(): ?FossilSource
    {
        $source = $this->validated('source');

        return is_string($source) ? FossilSource::tryFrom($source) : null;
    }

    public function scientificName(): ?string
    {
        $scientificName = $this->validated('scientific_name');

        return is_string($scientificName) ? $scientificName : null;
    }

    public function perPage(): int
    {
        $perPage = $this->validated('per_page', 15);

        return is_numeric($perPage) ? (int) $perPage : 15;
    }
}
