# Архитектура

## Три точки проводки

Проводка бандла держится на трёх файлах, которые обязаны оставаться в синхроне:

1. **`src/LoggerBundle.php`** — регистрирует `LoggerExtension` и добавляет
   компилятор-пасс `AddExceptionFilterPass` в `build()`.
2. **`src/Resources/config/services.yaml`** — PSR-4-скан с
   `autowire`/`autoconfigure`, который **исключает** `DependencyInjection/`,
   `Monolog/Handler/`, `Monolog/Processor/` и сам `LoggerBundle.php`.
   Процессоры регистрируются явно, каждый со своим тегом `monolog.processor`
   (`ConsoleProcessor`, `EnvironmentProcessor`, `ExceptionContextProcessor`,
   `UserProcessor`, `WebProcessor`). Новый процессор нужно добавить в этот
   явный список — resource-scan его не подхватит.
3. **`src/DependencyInjection/Compiler/AddExceptionFilterPass.php`** — обходит
   все `ContainerBuilder`-определения, чей id начинается с `monolog.logger`,
   пропускает абстрактные и уже задекорированные сервисы и оборачивает
   остальные в `ExceptionFilterDecorator`. Так фильтрация исключений работает
   для *каждого* канала без индивидуальной настройки.

## Поток фильтрации исключений

`ExceptionFilterDecorator` (`src/Monolog/Handler/`) — это декоратор
`Psr\Log\LoggerInterface` (не Monolog-хендлер, несмотря на директорию).
В `log()` он смотрит на `$context['exception']`: если это инстанс любого
класса из `logger_bundle.exception_classes`, вызов молча гасится —
**кроме** `debug()`, который проходит всегда. Проверка через
`array_any()` (нативная функция PHP 8.4). Асимметрия `debug()` — осознанное
решение; при добавлении нового уровня логирования её нужно сохранить.

## Пара форматтеров

- **`SwitchFormatter`** выбирает между `monolog.formatter.line` (человекочитаемый)
  и `JsonFormatter` по условию: `PHP_SAPI === 'cli'` (или нет текущего
  `Request`) **и** непустой env `HUMAN_READABLE`.
- **`JsonFormatter`** расширяет `Monolog\Formatter\JsonFormatter` и после
  нормализации записи:
  - добавляет `application`/`component` из
    `logger_bundle.applicationName`/`componentName` (источники —
    `APPLICATION_NAME`/`COMPONENT_NAME`, дефолт `unknown`);
  - переносит `count`/`size`/`duration` (int/float) и `id`/`status`
    (приводятся к строке) из `context` в отдельный `metrics`;
  - пустые `context`/`extra`/`metrics` сериализует как `{}` через `stdClass`,
    а не `[]`.

## Контекстно-зависимые исключения

`ExceptionContextProcessor` обходит цепочку `$context['exception']`
(`getPrevious()`, защита от цикла через `spl_object_hash`) и мержит
`getContext()` каждого исключения, реализующего
`ContextAwareExceptionInterface`, в контекст записи лога. Потребители
подключают `ContextAwareExceptionTrait` для готовой реализации
`getContext()`/`setContext()`.

## Опциональность Sentry-интеграции

`Sentry\Integration\LoggerIntegration` — **opt-in**: не автоконфигурируется,
потребитель сам добавляет её в `sentry.options.integrations`
(`config/packages/sentry.yaml`). Она добавляет теги `application`/`component`
в каждое событие Sentry через `Scope::addGlobalEventProcessor()`.

После Task 9 `sentry/sentry` — **require-dev** в обоих манифестах
(`composer.json` и `composer-ci.json`), поэтому класс
`Sentry\Integration\IntegrationInterface` резолвится на обоих путях и файл
полностью анализируется PHPStan на обоих манифестах (исключение из
`phpstan.dist.neon` убрано). Причина и история — в
[`tooling.md`](tooling.md) и [`known-issues.md`](known-issues.md).
Rector тоже обрабатывает `src/Sentry/` (общий `rector.php` из `bundle-standard`
без исключений; с 2026-10-01 UTC).
