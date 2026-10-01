# Тестирование

## Единственный suite

`phpunit.xml.dist` объявляет один suite — `unit`, директория `tests`.
Никакого Symfony kernel / integration-харнесса в бандле нет: процессоры и
форматтеры инстанцируются напрямую с моками их зависимостей
(`TokenStorageInterface`, `RequestStack` и т.п. — обычные PHPUnit mocks/stubs,
без `KernelTestCase`).

Запуск: `make test` (= `vendor/bin/phpunit`). Один тест:
`vendor/bin/phpunit --filter <TestName>` или путь к файлу, например
`vendor/bin/phpunit tests/Unit/Monolog/Formatter/JsonFormatterTest.php`.

`phpunit.xml.dist` — строгий: `failOnRisky`, `failOnWarning`,
`failOnPhpunitDeprecation`, `beStrictAboutOutputDuringTests`. Новое
предупреждение/deprecation/вывод в stdout — падение suite, а не повод для
игнора.

## `RecordingLogger` — образец фикстуры

`tests/Unit/Monolog/Handler/Fixture/RecordingLogger.php` — in-memory PSR-3
логгер (`extends AbstractLogger`), который пишет каждый вызов `log()` в
публичный `list<array{level: string, message: string|Stringable, context: array<array-key, mixed>}> $records`.
Используется в `ExceptionFilterDecoratorTest` для проверки, что декоратор
either пропускает, либо гасит запись, без моков ожиданий вызова — читать
`$records` после прогона проще, чем настраивать mock expectations. Тот же
паттерн стоит переиспользовать для новых тестов декораторов/обёрток над
`LoggerInterface`, а не писать mock-based варианты заново.

## Покрытие

`make test-with-coverage` — HTML-отчёт в `coverage/`.

Известный пробел: `src/Sentry/Integration/LoggerIntegration.php` не имеет
тестов вообще (см. `known-issues.md`) — Task 9 менял в нём `instanceof`-проверку
без регрессионного теста.
