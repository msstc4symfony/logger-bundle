# Тестирование

## Два suite

`phpunit.xml.dist` (шаблон `bundle-standard`) объявляет `unit` (`tests/Unit`)
и `integration` (`tests/Integration`).

- **unit** — процессоры, форматтеры, декоратор и Sentry-интеграция
  инстанцируются напрямую, зависимости — обычные PHPUnit stubs/реальные
  объекты (`RequestStack`, `TokenStorageInterface`), без `KernelTestCase`.
  Infection гоняет только этот suite (`infection.json5`).
- **integration** — `tests/Integration/Kernel/TestKernel.php` (MicroKernel:
  FrameworkBundle + MonologBundle + LoggerBundle, кэш в `sys_get_temp_dir()`
  на PID) и `KernelLoggingTest`: проверка реальной проводки (процессоры,
  фильтр исключений, `JsonFormatter` с переводом строки по умолчанию).
  `LoggerBundleConfigTest` грузит расширение бандла в голый `ContainerBuilder` и
  проверяет дефолты и валидацию `msstc4symfony_logger`.
  `php_errors.log: false` в ядре — иначе глобальный хендлер переживает ядро и
  срабатывает `failOnRisky`.

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

## Sentry: глобальное состояние SDK

`tests/Unit/Sentry/Integration/LoggerIntegrationTest.php` работает с реальным
SDK (`ClientBuilder` без DSN → null-транспорт). `Scope::addGlobalEventProcessor()`
пишет в приватный статический `Scope::$globalEventProcessors`, а сборка
`Client` сама вызывает `setupOnce()` через процессный `IntegrationRegistry`
(только в первый раз за процесс). Поэтому тест сбрасывает этот статический
массив через `ReflectionProperty` в `setUp`/`tearDown` и после сборки клиента,
а затем вызывает `setupOnce()` явно; `tearDown` делает `SentrySdk::init()`.
Без сброса тесты зависят от порядка запуска.

`KernelLoggingTest::tearDown()` разворачивает exception handler'ы, оставленные ядром (lowest Symfony 7.4), — см. [`known-issues.md`](known-issues.md).

`array_any()` в `ExceptionFilterDecorator` на PHP 8.4+ нативная; путь через `symfony/polyfill-php84` проверяют
только ячейки PHPUnit на 8.2/8.3 (матрица стандарта) или локальный прогон в Docker `php:8.2-cli`.
