# CLAUDE.md

Guidance for Claude Code working in this repository. Deep references live
under `.claude/docs/`; this file stays lean.

## What this is

Symfony bundle (`msstc4symfony/logger-bundle`, namespace `Msstc4Symfony\LoggerBundle`)
that extends Monolog with context-aware processors, formatters, an
exception-filtering decorator, and an opt-in Sentry integration. Requires
PHP >= 8.4 and Symfony 6.4 LTS / 7.x / 8.x. Library code only — no host
application in the repo.

PHP 8.4 is mandatory: the bundle uses native `array_any()`, first-class
callable syntax, and `#[Override]` throughout. Do not propose backports.
Unlike `healthcheck-bundle`, it does not use asymmetric visibility or
property hooks — see `.claude/docs/conventions.md` before copying those
idioms over by analogy.

## Common commands

All in `Makefile` (the standard's 7-target layout):

- `make check` — full quality gate: `php -l` on every non-vendor PHP file,
  PHPStan (level 9, `--memory-limit=512M`), PHP-CS-Fixer check,
  `composer validate --strict --no-check-publish`, `composer audit`,
  Rector dry-run, `deptrac analyse`.
- `make test` — `vendor/bin/phpunit`.
- `make test-with-coverage` — HTML coverage report into `coverage/`.
- `make infection` — mutation testing (not part of `make check` — too slow).
- `make regenerate-baseline` — rewrite `phpstan-baseline.neon` after
  intentional PHPStan changes.
- `make fix` — apply PHP-CS-Fixer and Rector autofixes.
- `make help` — list targets (default goal).

Single test: `vendor/bin/phpunit --filter <TestName>` or
`vendor/bin/phpunit tests/unit/Monolog/Formatter/JsonFormatterTest.php`.

PHPUnit config is strict (`failOnRisky`, `failOnWarning`,
`failOnPhpunitDeprecation`, `beStrictAboutOutputDuringTests`) — silence
warnings/output rather than ignoring them.

`make check` runs against whichever manifest is installed in `vendor/`. It
does not pass end-to-end on a plain `composer.json` install (`deptrac` is
declared only in `composer-ci.json`) — see
[`.claude/docs/tooling.md`](.claude/docs/tooling.md) before assuming a
failure is a regression.

## Architecture in 60 seconds

The bundle's wiring lives in three places that must stay in sync:

1. **`src/LoggerBundle.php`** registers the extension and the
   `AddExceptionFilterPass` compiler pass.
2. **`src/Resources/config/services.yaml`** uses `autowire`/`autoconfigure`
   with a PSR-4 resource scan that **excludes** `Monolog/Processor/` and
   `Monolog/Handler/` — those are wired explicitly. New processors must be
   added to the explicit `monolog.processor` tag list there; new
   handlers/decorators do not autoload via the resource scan.
3. **`src/DependencyInjection/Compiler/AddExceptionFilterPass.php`** walks
   every service whose id starts with `monolog.logger`, skips abstracts and
   existing decorators, and wraps each with `ExceptionFilterDecorator`. This
   is how exception filtering applies to *every* channel without per-channel
   config.

`ExceptionFilterDecorator` silently drops records whose `$context['exception']`
matches `logger_bundle.exception_classes` — except `debug()`, which always
passes through. `SwitchFormatter` / `JsonFormatter` are the two formatters;
`ExceptionContextProcessor` merges context from exceptions implementing
`ContextAwareExceptionInterface`. The Sentry integration
(`Sentry\Integration\LoggerIntegration`) is opt-in — not autoconfigured, a
consumer must add it to `sentry.options.integrations`. Full detail in
[`.claude/docs/architecture.md`](.claude/docs/architecture.md).

## Pointers to deep references

Read these when the task touches the area:

- [`.claude/docs/architecture.md`](.claude/docs/architecture.md) — the
  three wiring points, exception-filtering flow, formatter pair,
  context-aware exceptions, Sentry integration.
- [`.claude/docs/conventions.md`](.claude/docs/conventions.md) — PHP 8.4
  idioms, naming, comment policy, baseline-growth ban.
- [`.claude/docs/testing.md`](.claude/docs/testing.md) — the single `unit`
  suite, no kernel harness, `RecordingLogger` fixture.
- [`.claude/docs/tooling.md`](.claude/docs/tooling.md) — `make check`
  breakdown, baseline policy, two-manifest setup, deptrac rules.
- [`.claude/docs/ci.md`](.claude/docs/ci.md) — reusable-workflow model
  from `bundle-standard@v1`, how to change the matrix.
- [`.claude/docs/known-issues.md`](.claude/docs/known-issues.md) —
  gotchas and deferred work. **Check this before chasing a "weird"
  failure.**
