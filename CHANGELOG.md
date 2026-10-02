# Changelog

All notable changes to this bundle are documented here. Versions follow
[Semantic Versioning](https://semver.org/); dates are UTC.

## [1.2.0] - 2026-10-02

### Changed

- `monolog/monolog: ^3.0` is now declared in `require`. The bundle is written
  against the Monolog 3 API (`LogRecord`, `Level`) but only received Monolog
  transitively, so a resolver could pick Monolog 2 and fail with a fatal error
  in `SwitchFormatter::format()`.
- CI adopts `bundle-standard` v1.8.0: blocking Roave BC check, blocking
  Infection (minimum MSI and covered MSI 78 %), a `--prefer-lowest` PHPUnit
  cell, PHPStan level 10.
- Development: `phpunit/phpunit` minimum raised to 11.5.50 (the shared
  `phpunit.xml.dist` needs it); the CI manifest conflicts with
  `symfony/error-handler` versions that leak an exception handler under PHPUnit.

### Fixed

- `JsonFormatter`: the private metrics collector declared the normalized
  context as `array<string, mixed>`, while Monolog allows integer keys. No
  runtime behaviour change.

### Tests

- `Sentry\Integration\LoggerIntegration` is covered: tags, single global
  processor, lookup through the current hub, events without tags when the hub
  has no client or no integration.

## [1.1.2] - 2026-10-01

### Fixed

- `JsonFormatter` writes one JSON document per line in stream handlers;
  `SwitchFormatter::formatBatch()` delegates to the selected formatter.

Earlier releases are described by their git tags.

[1.2.0]: https://github.com/msstc4symfony/logger-bundle/compare/v1.1.2...v1.2.0
[1.1.2]: https://github.com/msstc4symfony/logger-bundle/compare/v1.1.1...v1.1.2
