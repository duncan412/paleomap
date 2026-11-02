<?php

declare(strict_types=1);

namespace App\Services;

use Brick\Geo\Point;

final class SpatialService
{
    public function createPointFromCoords(?float $lat, ?float $lon): ?Point
    {
        if ($lat === null || $lon === null) {
            return null;
        }

        if (! is_finite($lat) || ! is_finite($lon) || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        return Point::xy($lon, $lat, 4326);
    }

    /**
     * Bbox follows the GeoJSON order: [west, south, east, north].
     *
     * @param  array<array-key, mixed>  $bbox
     */
    public function isPointInBoundingBox(Point $point, array $bbox): bool
    {
        if (count($bbox) !== 4 || ! array_is_list($bbox)) {
            return false;
        }

        $west = $bbox[0] ?? null;
        $south = $bbox[1] ?? null;
        $east = $bbox[2] ?? null;
        $north = $bbox[3] ?? null;

        if (! is_numeric($west) || ! is_numeric($south) || ! is_numeric($east) || ! is_numeric($north)) {
            return false;
        }

        $west = (float) $west;
        $south = (float) $south;
        $east = (float) $east;
        $north = (float) $north;

        if (
            ! is_finite($west)
            || ! is_finite($south)
            || ! is_finite($east)
            || ! is_finite($north)
            || $west < -180
            || $east > 180
            || $south < -90
            || $north > 90
            || $west > $east
            || $south > $north
        ) {
            return false;
        }

        $longitude = $point->x();
        $latitude = $point->y();

        return $longitude !== null
            && $latitude !== null
            && $longitude >= $west
            && $longitude <= $east
            && $latitude >= $south
            && $latitude <= $north;
    }
}
