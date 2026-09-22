# Security Policy

## Supported Versions

The bundle follows semantic versioning. Security fixes are released for the latest
minor on each supported major.

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

Runtime requirements are tracked in `composer.json`:

- PHP >= 8.4 (no support for earlier minors — the bundle relies on PHP 8.4
  features: readonly promoted properties, typed class constants, `#[Override]`)
- Symfony 6.4 LTS, 7.x, 8.x

If you are running an older PHP or Symfony version, upgrade before reporting.

## Reporting a Vulnerability

Please **do not** open public GitHub issues, pull requests, or discussions for
security problems.

Use one of the following private channels:

1. **GitHub Security Advisory** (preferred):
   <https://github.com/max-shamaev-php/logger-bundle/security/advisories/new>
2. **Email**: `maxim.shamaev@gmail.com`

Include, where possible:

- Affected bundle version and PHP/Symfony versions
- A reproducer (failing test, log record example, or minimal app config)
- Impact and exploitation scenario
- Suggested remediation, if any

You will receive an acknowledgement within **7 days**. Once the report is
triaged, you'll get a timeline for a fix. Coordinated disclosure is appreciated:
please give the maintainer a reasonable window (typically 30–90 days, depending
on severity) before publishing details.

## Scope

This bundle is library code consumed by a host Symfony application. The
following components are in scope for security reports:

- Processors (`ConsoleProcessor`, `EnvironmentProcessor`,
  `ExceptionContextProcessor`, `UserProcessor`, `WebProcessor`)
- Formatters (`JsonFormatter`, `SwitchFormatter`)
- `ExceptionFilterDecorator`
- The compiler pass `AddExceptionFilterPass`
- The Sentry integration (`Sentry\Integration\LoggerIntegration`)

The following are **out of scope** because they are the host application's
responsibility:

- What a consumer's own exceptions attach via `ContextAwareExceptionTrait`
  (see [Threat Model](#threat-model) below)
- The configuration of `logger_bundle.exception_classes` and Monolog handlers
- Transport, storage, retention, and access control of the log sink itself
  (stdout, file, Sentry project, log aggregator)
- Behaviour of third-party clients (`symfony/monolog-bundle`, `sentry/sentry`)

## Threat Model

The bundle enriches and filters log records; it does not create a network
surface of its own. Below are the three real attack/risk surfaces.

### 1. Sensitive data reaching log sinks

`ExceptionContextProcessor` walks the `$context['exception']` chain and merges
`getContext()` from every exception implementing `ContextAwareExceptionInterface`
into the log record. Whatever a consumer attaches via `ContextAwareExceptionTrait`
ends up in the log pipeline verbatim — credentials, tokens and PII included.
Audit what your exceptions carry before enabling DEBUG-level transport.

### 2. `UserProcessor` and identity disclosure

`UserProcessor` enriches records with the authenticated user identifier from
`symfony/security-bundle`. In shared or third-party log sinks this is personal
data; ensure your retention policy covers it.

### 3. Exception filtering is not a security control

`ExceptionFilterDecorator` silently drops records whose `$context['exception']`
matches `logger_bundle.exception_classes` — except `debug()`, which always passes
through. Do not rely on it to suppress sensitive output: the `debug()` asymmetry
is intentional and documented, and a misconfigured level will surface everything.

## Operational Guidance

Recommended deployment posture:

- **Sentry integration is opt-in.** `LoggerIntegration` is not autoconfigured;
  it only runs when a consumer adds it to `sentry.options.integrations`. Review
  what reaches Sentry the same way you'd review any other log sink.
- **Treat `HUMAN_READABLE` as a local/dev-only toggle.** `SwitchFormatter`
  switches to a human-readable line formatter in CLI when the env var is set;
  don't set it in production where structured JSON output is expected by log
  aggregation.
- **Audit exception classes before adding them to `logger_bundle.exception_classes`.**
  Every class added there stops reaching your logs at every level except
  `debug()` — including future subclasses you may not anticipate.

## Dependency & Static-Analysis Hygiene

The project's quality gate (`make check`) enforces several security-relevant
controls:

- `composer audit` runs on every CI build; advisories from
  `roave/security-advisories` block dependency installation.
- PHPStan level 9 with `spaze/phpstan-disallowed-calls`, `phpstan-strict-rules`,
  and `phpstan-symfony` rules, run against both the plain and the CI manifest.
- Rector applies the PHP 8.4 quality and dead-code rulesets.
- PHPUnit runs in strict mode (`failOnRisky`, `failOnWarning`,
  `failOnPhpunitDeprecation`).

When contributing, do not bypass `make check` (e.g., via `--no-verify`). New
findings are not auto-baselined.

## Credits

Thank you to anyone who responsibly discloses a vulnerability. Reporters are
acknowledged in release notes unless they request otherwise.
