# Архитектура

## Три точки проводки

Проводка бандла держится на трёх файлах, которые обязаны оставаться в синхроне:

1. **`src/LoggerBundle.php`** — `AbstractBundle` с алиасом `msstc4symfony_logger`.
   `configure()` описывает дерево конфигурации (`application_name`,
   `component_name` — непустые строки (`stringNode()`, int/bool отклоняются, как в
   tracing/metrics), `exception_classes`; каждый элемент `exception_classes` —
   существующий класс `Throwable`), `loadExtension()` импортирует `services.php` и
   кладёт значения в параметры контейнера `msstc4symfony_logger.application_name`,
   `.component_name`, `.exception_classes` (их читают `JsonFormatter`,
   `LoggerIntegration`, `ExceptionFilterDecorator` через `#[Autowire(param:)]`).
   `build()` добавляет компилятор-пасс `AddExceptionFilterPass`.
2. **`src/Resources/config/services.php`** — явная регистрация сервисов
   (`JsonFormatter`, `SwitchFormatter`, `Sentry\Integration\LoggerIntegration` (только если установлен `sentry/sentry`), пять процессоров с тегом
   `monolog.processor`: `ConsoleProcessor`, `EnvironmentProcessor`,
   `ExceptionContextProcessor`, `UserProcessor`, `WebProcessor`) с
   `autowire`/`autoconfigure` и биндингами `$batchMode`/`$includeStacktraces`.
   Автосканирования каталога нет: новый процессор или форматтер нужно добавить
   в этот файл. Заодно файл задаёт параметр `msstc4symfony_logger.unknown`,
   на который ссылаются дефолты `%env(default:…)%`.
3. **`src/DependencyInjection/Compiler/AddExceptionFilterPass.php`** — обходит
   все `ContainerBuilder`-определения, чей id начинается с `monolog.logger`,
   пропускает абстрактные и уже задекорированные сервисы и оборачивает
   остальные в `ExceptionFilterDecorator`. Так фильтрация исключений работает
   для *каждого* канала без индивидуальной настройки.

## Поток фильтрации исключений

`ExceptionFilterDecorator` (`src/Monolog/Handler/`) — это декоратор
`Psr\Log\LoggerInterface` (не Monolog-хендлер, несмотря на директорию).
В `log()` он смотрит на `$context['exception']`: если это инстанс любого
класса из `msstc4symfony_logger.exception_classes`, вызов молча гасится —
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
    `msstc4symfony_logger.application_name`/`.component_name` (дефолт —
    env `APPLICATION_NAME`/`COMPONENT_NAME`, при отсутствии `unknown`);
  - `$appendNewline` по умолчанию `true`: одна запись — одна строка для
    line-based шиппера (Loki, Fluent Bit, ELK);
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
в каждое событие Sentry через `Scope::addGlobalEventProcessor()`. Процессор
статичен и при каждом событии берёт интеграцию из клиента текущего хаба
(`SentrySdk::getCurrentHub()->getIntegration(self::class)`); нет клиента или
интеграции — событие уходит без тегов. Покрыто `LoggerIntegrationTest`.

После Task 9 `sentry/sentry` — **require-dev** в обоих манифестах
(`composer.json` и `composer-ci.json`), поэтому класс
`Sentry\Integration\IntegrationInterface` резолвится на обоих путях и файл
полностью анализируется PHPStan на обоих манифестах (исключение из
`phpstan.dist.neon` убрано). Причина и история — в
[`tooling.md`](tooling.md) и [`known-issues.md`](known-issues.md).
Rector тоже обрабатывает `src/Sentry/` (общий `rector.php` из `bundle-standard`
без исключений; с 2026-10-01 UTC).

## Не-final классы

Нет: все конкретные классы бандла `final`; не-final только интерфейс `ContextAwareExceptionInterface` и трейт `ContextAwareExceptionTrait`.
