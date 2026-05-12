---
name: quality-gate
description: Run the full project quality gate (make check + make test) and return a structured, deduplicated report. Use when the user asks "is this ready", "run checks", "проверь", before opening a PR, or after a non-trivial change. Skip for doc-only or comment-only edits.
---

# Quality gate for logger-bundle

This skill runs the project's full local CI and turns the output into something actionable. It does not auto-fix — that is the user's call.

## What to run

Run in this order. **Stop early only on a fatal tool failure (e.g. composer can't find vendor/)**; lint findings are expected and should be collected, not aborted on.

```bash
make check
make test
```

`make check` expands to:
- `find ./ -name '*.php' -not -path './vendor/*' | xargs -r php -l` — syntax check
- `vendor/bin/phpstan --memory-limit=512M` — level 9
- `vendor/bin/php-cs-fixer check` — code style
- `composer audit` — security advisories
- `vendor/bin/rector process -n` — dry-run refactoring suggestions

`make test` runs `vendor/bin/phpunit` with strict settings (`failOnRisky`, `failOnWarning`, `failOnPhpunitDeprecation`).

## How to report

Produce a single markdown report with these sections, in order. Omit sections that have zero findings — never write "no issues" subsections.

### 1. Verdict (one line)
`PASS` if everything is green, `FAIL` otherwise. No emoji.

### 2. Blockers
Anything that would fail CI: PHPStan errors, PHPUnit failures/errors, `composer audit` advisories, `php -l` syntax errors. Format each as:

```
- <tool> · <path>:<line> — <one-line message>
```

### 3. Style / refactor suggestions
PHP-CS-Fixer and Rector findings. These would all be fixed by `make fix`, so collapse them: report the **count** per tool and the **list of distinct files** touched, not every diff. If the user wants details, they can run `make fix` and inspect.

### 4. Warnings worth a look
PHPUnit risky/deprecation warnings, PHPStan ignored errors, anything noted in `phpstan-baseline.neon` that the current diff touches.

### 5. Next step
One sentence. Examples:
- "Run `make fix` to auto-resolve the 12 style/refactor findings, then re-run this skill."
- "Fix the PHPStan error in `src/Monolog/Formatter/JsonFormatter.php:73` (it can't be auto-fixed) before opening a PR."
- "All clear — ready to commit."

## Rules

- **Do not modify files.** This is a check, not a fix. If asked to fix, suggest `make fix` and stop.
- **Do not touch `phpstan-baseline.neon`.** Regenerating it (`make regenerate-baseline`) is an explicit user decision.
- **Do not re-run on no changes.** If the user invokes the skill twice in a row with nothing modified between, say so and reuse the prior result.
- **Cite file:line for every finding.** If the tool's output doesn't include a line, quote the relevant snippet instead — never fabricate.
- **Strip vendor noise.** Findings under `vendor/` or `coverage/` are not actionable and must not appear in the report.
