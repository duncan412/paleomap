# Fossil Data Hub

Fossil Data Hub is a Laravel 12 application that ingests fossil occurrences from
the Naturalis Biodiversity API v2 and the GBIF occurrence API. Records retain
the original API payload, geographic coordinates, and a WGS 84 point geometry.

## Requirements

- PHP 8.5
- Composer
- PostgreSQL with PostGIS, or MySQL with spatial support

SQLite remains suitable for the automated test suite.

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` to use the target spatial database. For PostGIS, set
`DB_CONNECTION=pgsql` and the normal PostgreSQL connection values. The fossils
migration enables the `postgis` extension and creates a `geometry(POINT, 4326)`
column. The database role must be allowed to create the extension, or an
administrator must enable it before migrations run. MySQL uses its native
SRID-aware `POINT` type.

Then run:

```sh
php artisan migrate
```

## Ingest fossils

```sh
php artisan fossils:ingest --source=naturalis
php artisan fossils:ingest --source=gbif
php artisan fossils:ingest --source=all
```

Records are upserted by `(source, external_id)`. Invalid or missing spatial
coordinates are stored as null; valid points use longitude as X, latitude as Y,
and SRID 4326.

## Fossil API

- `GET /api/fossils` lists fossils and supports `source`, `scientific_name`, and
  `per_page` query parameters (`per_page` is limited to 100).
- `GET /api/fossils/{uuid}` returns one fossil.

Responses are transformed with League Fractal and use a `data` envelope;
collection responses also include pagination metadata. Scribe serves the
generated API documentation at `/docs`.

The endpoint descriptions and query-parameter docs live beside the controller
methods in `app/Http/Controllers/Api/FossilController.php`. After changing API
routes or annotations, regenerate and commit the static docs:

```sh
composer docs:generate
```

`composer docs:check` regenerates the docs and fails if `public/docs` differs
from the committed version. A GitHub Actions workflow runs this check on pushes
and pull requests, so stale docs are caught before merging.

## Quality checks

```sh
vendor/bin/pest
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
vendor/bin/pint --test
```
