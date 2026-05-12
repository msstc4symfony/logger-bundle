---
name: new-processor
description: Scaffold a new Monolog processor in this bundle — creates the class in src/Monolog/Processor/, registers it in services.yaml with the monolog.processor tag, and adds a matching unit test. Use when the user asks to "add a processor", "create a Monolog processor", "добавь процессор", or similar. Do NOT use for formatters, handlers, or decorators (different wiring rules).
---

# Scaffold a Monolog processor

## Preconditions to gather first

Before writing anything, ask (or infer from the request) and confirm:

1. **Processor name.** Class name in PascalCase ending in `Processor` (e.g. `RequestIdProcessor`). The filename matches.
2. **What it adds.** Which keys it writes onto `LogRecord::$extra` (or `$context`), and the type of each.
3. **What it depends on.** What services it needs in its constructor (`RequestStack`, `Security`, env vars, parameters, etc.). If none, the constructor stays empty.

If any of these is unclear, ask one short clarifying question before scaffolding. Do not invent dependencies.

## Files to create

### 1. `src/Monolog/Processor/<Name>Processor.php`

Match the existing style — see `src/Monolog/Processor/WebProcessor.php` as the canonical template. Required shape:

```php
<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class <Name>Processor implements ProcessorInterface
{
    public function __construct(
        // injected dependencies, readonly, promoted
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        // guard clauses that return $record unchanged when the data is unavailable
        // (e.g. no current request, no authenticated user — see WebProcessor / UserProcessor)

        $record->extra['<key>'] = <value>;

        return $record;
    }
}
```

Rules:
- `declare(strict_types=1)` is mandatory.
- Class is **not** `final` by default — none of the existing processors are. Match the convention; only mark `final` if the user explicitly asks.
- Guard clauses must short-circuit with `return $record;` when the data source is unavailable. Never throw.
- Constructor properties are `private readonly` and promoted.
- For env-var or parameter injection use `#[Autowire(param: 'logger_bundle.xxx')]` — see `JsonFormatter` for the pattern.

### 2. Register in `src/Resources/config/services.yaml`

The PSR-4 resource scan **excludes** `Monolog/Processor/`, so autoconfiguration alone is not enough. Add an explicit entry, keeping the list alphabetically sorted by class name:

```yaml
  MaxShamaev\LoggerBundle\Monolog\Processor\<Name>Processor:
    tags:
      - { name: monolog.processor }
```

Do not remove the `# Processors` comment block. Do not change `_defaults` or the `MaxShamaev\LoggerBundle\:` resource block.

### 3. `tests/unit/Monolog/Processor/<Name>ProcessorTest.php`

Match the existing test style. Look at `tests/unit/Monolog/Processor/WebProcessorTest.php` (HTTP-context processor with `RequestStack`) or `EnvironmentProcessorTest.php` (no Symfony deps) — pick the closer template. Required coverage:

- One test that the processor adds the expected keys with the expected types when its data source is available.
- One test that the processor returns the record unchanged (no keys added) when the data source is absent — exercise every guard clause.

Tests instantiate the processor directly with mocks. Do not use a Symfony kernel. The `LogRecord` can be built with `new LogRecord(...)` — see existing tests for the argument shape.

Watch out: PHPUnit is configured with `failOnRisky`, `failOnWarning`, `beStrictAboutOutputDuringTests`. Every test method must have at least one assertion and must not echo or `var_dump`.

The dev autoload PSR-4 prefix is `MaxShamaev\HealthCheckBundle\Test\Unit\` (a copy-paste leftover from another bundle, documented in CLAUDE.md). Use the same namespace pattern as the neighboring test files — do not "correct" it.

## After scaffolding

Run `make check && make test` and report the outcome. If anything fails, fix the cause in the scaffolded files — do not edit `phpstan-baseline.neon`.

## What this skill does NOT do

- Does not register a formatter, handler, or decorator. Those have different wiring rules — refer the user to the `monolog-architect` subagent.
- Does not add the processor to a specific Monolog channel. Tagging with `monolog.processor` applies it to all channels; channel-specific binding is configured in the consumer's `monolog.yaml`, not in this bundle.
- Does not add documentation to README.md unless the user explicitly asks.
