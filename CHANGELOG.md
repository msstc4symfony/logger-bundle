# Changelog

All notable changes to this bundle are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow
[Semantic Versioning](https://semver.org/); dates are UTC.

## [2.0.0-rc.1] - 2026-10-19

### Added

- README "Public API": the classes and configuration keys applications may rely on.

### Changed

- `Msstc4Symfony\LoggerBundle\Monolog\ContextAwareExceptionInterface` and `ContextAwareExceptionTrait` →
  `Msstc4Symfony\LoggerBundle\Application\Exception\*`.
- `Msstc4Symfony\LoggerBundle\Monolog\{Formatter,Handler,Processor}\*` →
  `Msstc4Symfony\LoggerBundle\Framework\Monolog\{Formatter,Handler,Processor}\*` — update `SwitchFormatter` in
  `monolog.yaml`.
- `Msstc4Symfony\LoggerBundle\Sentry\Integration\LoggerIntegration` →
  `Msstc4Symfony\LoggerBundle\Framework\Sentry\Integration\LoggerIntegration` — update `sentry.yaml`.
- `Msstc4Symfony\LoggerBundle\DependencyInjection\Compiler\AddExceptionFilterPass` →
  `Msstc4Symfony\LoggerBundle\DependencyInjection\CompilerPass\AddExceptionFilterPass`.
- Classes outside the README "Public API" are `@internal`: `JsonFormatter`, `ExceptionFilterDecorator`, the
  processors and the compiler pass.
- PHPStan enforces `#[\Override]` on every overriding or implementing method (`checkMissingOverrideMethodAttribute`).
- Configuration keys are unchanged.
- Adopts bundle-standard v3 (canonical hex layout, anchored deptrac `BundleRoot`).

## [1.1.0] - 2026-10-14

### Added

- `symfony/polyfill-php84` dependency (`array_any` on PHP 8.2/8.3).

### Changed

- Requires PHP 8.2 instead of 8.4 (Symfony 8 still needs PHP 8.4).

## [1.0.0] - 2026-10-04

First release of `msstc4symfony/logger-bundle` (namespace `Msstc4Symfony\LoggerBundle`).

### Added

- Monolog processors that enrich every record: `WebProcessor`, `UserProcessor`,
  `ConsoleProcessor`, `EnvironmentProcessor` and `ExceptionContextProcessor`
  (context from exceptions implementing `ContextAwareExceptionInterface`, with
  `ContextAwareExceptionTrait`).
- Formatters: `JsonFormatter` (one JSON document per line, structured metrics
  block) and `SwitchFormatter` (human-readable or JSON output).
- `ExceptionFilterDecorator`, applied by a compiler pass to every
  `monolog.logger*` channel: drops records whose `context.exception` matches a
  configured class; `debug()` records always pass.
- Opt-in Sentry integration `Sentry\Integration\LoggerIntegration` that tags
  events with the application and component names.
- Configuration under the `msstc4symfony_logger` root: `application_name`,
  `component_name`, `exception_classes`.

### Requirements

- PHP >= 8.4, Symfony ^7.4|^8.0, Monolog ^3.5, `symfony/monolog-bundle` ^3.11|^4.0.
- `sentry/sentry` 4.x only for the optional Sentry integration.

[2.0.0-rc.1]: https://github.com/msstc4symfony/logger-bundle/releases/tag/v2.0.0-rc.1
[1.1.0]: https://github.com/msstc4symfony/logger-bundle/releases/tag/v1.1.0
[1.0.0]: https://github.com/msstc4symfony/logger-bundle/releases/tag/v1.0.0
