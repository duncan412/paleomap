<?php

declare(strict_types=1);

use App\Services\SpatialService;
use Brick\Geo\Point;

it('creates an EPSG 4326 point with longitude as x and latitude as y', function (): void {
    $point = (new SpatialService)->createPointFromCoords(52.1, 5.1);

    expect($point)
        ->toBeInstanceOf(Point::class)
        ->and($point?->x())->toBe(5.1)
        ->and($point?->y())->toBe(52.1)
        ->and($point?->coordinateSystem()->srid())->toBe(4326);
});

it('returns null when either coordinate is missing', function (?float $latitude, ?float $longitude): void {
    expect((new SpatialService)->createPointFromCoords($latitude, $longitude))->toBeNull();
})->with([
    'missing latitude' => [null, 10.0],
    'missing longitude' => [10.0, null],
    'both missing' => [null, null],
]);

it('rejects coordinates outside geographic limits or not finite', function (float $latitude, float $longitude): void {
    expect((new SpatialService)->createPointFromCoords($latitude, $longitude))->toBeNull();
})->with([
    'latitude too low' => [-90.1, 0.0],
    'latitude too high' => [90.1, 0.0],
    'longitude too low' => [0.0, -180.1],
    'longitude too high' => [0.0, 180.1],
    'nan' => [NAN, 0.0],
    'infinity' => [0.0, INF],
]);

it('checks points against inclusive GeoJSON bounding boxes', function (): void {
    $service = new SpatialService;
    $point = Point::xy(5.0, 52.0, 4326);

    expect($service->isPointInBoundingBox($point, [5, 52, 5, 52]))->toBeTrue()
        ->and($service->isPointInBoundingBox($point, [4.0, 51.0, 6.0, 53.0]))->toBeTrue()
        ->and($service->isPointInBoundingBox($point, [5.1, 51.0, 6.0, 53.0]))->toBeFalse();
});

it('rejects malformed or out of range bounding boxes', function (array $bbox): void {
    expect((new SpatialService)->isPointInBoundingBox(Point::xy(5.0, 52.0), $bbox))->toBeFalse();
})->with([
    'too few coordinates' => [[0, 0, 1]],
    'associative coordinates' => [['west' => 0, 'south' => 0, 'east' => 1, 'north' => 1]],
    'reversed longitude bounds' => [[10, 0, 1, 1]],
    'reversed latitude bounds' => [[0, 10, 1, 1]],
    'longitude out of range' => [[-181, 0, 1, 1]],
    'non numeric coordinate' => [[0, 0, 'east', 1]],
]);

it('returns false for an empty point', function (): void {
    expect((new SpatialService)->isPointInBoundingBox(Point::xyEmpty(), [-180, -90, 180, 90]))->toBeFalse();
});
