# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
## Unreleased

### Added

- **api:** Expose fossil endpoints with Fractal and Scribe
- **console:** Implement fossils ingest artisan command
- **api:** Add Naturalis and GBIF API services
- **spatial:** Implement spatial service using brick/geo
- **database:** Create fossils table migration and model

### Maintenance

- Automate changelog and API documentation updates
- **database:** Include Laravel default migrations
- Initialize Laravel 12 on develop with PHPStan level 9 and Pest

### Tests

- **console:** Add feature tests for ingestion command

