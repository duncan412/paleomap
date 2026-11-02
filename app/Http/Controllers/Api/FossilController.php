<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FossilIndexRequest;
use App\Models\Fossil;
use App\Transformers\FossilTransformer;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

final class FossilController extends Controller
{
    public function __construct(
        private readonly Manager $fractal,
    ) {
        $this->fractal->setSerializer(new DataArraySerializer);
    }

    /**
     * List fossil records with optional source/name filters and pagination.
     *
     * @group Fossils
     *
     * @queryParam source string Filter by source. Allowed values: naturalis, gbif. Example: gbif
     * @queryParam scientific_name string Filter by a scientific name containing this text. Example: Tyrannosaurus
     * @queryParam per_page integer Number of records per page (1-100). Defaults to 15. Example: 25
     *
     * @transformerCollection App\Transformers\FossilTransformer
     *
     * @transformerModel App\Models\Fossil
     *
     * @transformerPaginator League\Fractal\Pagination\IlluminatePaginatorAdapter 15
     */
    public function index(FossilIndexRequest $request): JsonResponse
    {
        $fossils = Fossil::query()
            ->when(
                $request->source(),
                static fn ($query, $source) => $query->where('source', $source->value),
            )
            ->when(
                $request->scientificName(),
                static fn ($query, $name) => $query->where('scientific_name', 'like', '%'.$name.'%'),
            )
            ->orderBy('scientific_name')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        $resource = new Collection($fossils->items(), new FossilTransformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($fossils));

        return response()->json($this->fractal->createData($resource)->toArray());
    }

    /**
     * Return one fossil record by its UUID.
     *
     * @group Fossils
     *
     * @urlParam fossil string required The fossil UUID. Example: 018f47e2-8d35-7e8d-a5a2-f858b1b8d5c1
     *
     * @transformer App\Transformers\FossilTransformer
     *
     * @transformerModel App\Models\Fossil
     *
     * @response status=404 scenario="Fossil not found" {"message":"Not Found"}
     */
    public function show(Fossil $fossil): JsonResponse
    {
        $resource = new Item($fossil, new FossilTransformer);

        return response()->json($this->fractal->createData($resource)->toArray());
    }
}
