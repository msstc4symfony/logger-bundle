# Известные проблемы / особенности

Список для «уже наступали, можем наступить снова». Пополнять, не переписывать
целиком.

## Rector: `withSymfonyContainerPhp` указывает на несуществующий файл

`rector.php` вызывает
`->withSymfonyContainerPhp(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.php')`.
У бандла нет kernel и этот файл никогда не генерируется — Symfony-container-aware
правила Rector-а по факту инертны при прогоне здесь в одиночку. Это не баг и
чинить нечего: настройка скопирована из `healthcheck-bundle`, где кэш
контейнера реально есть (там есть тестовый kernel). Не пытаться "исправить"
путь или сгенерировать этот файл.

## `symfony/monolog-bundle` — `^3.11|^4.0`, не `^3.0`

monolog-bundle 3.x поддерживает Symfony максимум 7.4; свежий Symfony 8 ставит 4.x.
С `^3.0` бандл не устанавливался в проект на Symfony 8, а CI этого не видел: ячейка
«Symfony 8» фиксировала только framework-bundle/console, и Composer оставлял
`http-kernel` и остальное на 7.4. `bundle-standard` фиксирует http-kernel/DI/config и проверяет результат, поэтому такая ячейка падает
(шаг «Assert the matrix Symfony version was installed»).
Пол `^3.11` (и `monolog/monolog: ^3.5`) — общий для всех бандлов семейства
(выравнивание 2026-10-04 UTC, metrics/tracing/profiling уже на этих полах).

## `sentry/sentry` в `require-dev` обоих манифестов — не "тайди" обратно

См. `tooling.md` — `sentry/sentry` живёт и в `composer.json`, и в
`composer-ci.json`, потому что `LoggerIntegration implements
Sentry\Integration\IntegrationInterface`, а PHPStan не умеет
баслайнить "implements unknown interface" (`nonIgnorable()` в ядре
PHPStan). Перенос обратно в `composer-ci.json`-only снова сломает PHPStan
на плоском манифесте без возможности обойти это baseline'ом.

## `phpstan/phpstan-doctrine` убран из обоих манифестов

Скопирован по аналогии с `healthcheck-bundle`, но в `logger-bundle` нет
Doctrine-кода. Плагин вызывал нативный `class_exists()` на каждом
`final`-классе (`EntityNotFinalRule`), что при отсутствующем интерфейсе
Sentry на плоском манифесте роняло PHP на фатальной ошибке во время
компиляции `LoggerIntegration` — не PHPStan-находка, а падение движка.
Не возвращать без реального Doctrine-кода в бандле. Детали — `tooling.md`,
коммит `5ad60e7`.

## Оба composer-манифеста ставятся в один и тот же `vendor/`

Если нужно проверить поведение, зависящее от того, какой именно манифест
установлен (например, что PHPStan падает на плоском `composer.json` без
`sentry/sentry`) — **сначала `rm -rf vendor/`**. Иначе можно получить
зелёный результат по неверной причине: PHPStan подхватит пакеты,
поставленные предыдущим `COMPOSER=composer-ci.json composer install`. Эта
ошибка уже стоила одного цикла ревью в Task 9.

## `make check` не проходит end-to-end на чистой установке `composer.json`

`deptrac/deptrac` объявлен только в `composer-ci.json`. На плоском манифесте
`vendor/bin/deptrac` отсутствует, и последний шаг `make check` падает с
"command not found". Это свойство двухманифестного дизайна (то же самое у
`healthcheck-bundle`), не дефект. Для разработки и полного локального
прогона гейта:

```bash
COMPOSER=composer-ci.json composer install
PHPSTAN_CONFIG=phpstan-ci.neon make check
make test
```

## `LoggerIntegration` покрыт тестами

`LoggerIntegrationTest` (для `src/Sentry/Integration/LoggerIntegration.php`) проверяет: теги `application`/`component`
ставятся; ровно один глобальный процессор; значения берутся из интеграции,
зарегистрированной в клиенте **текущего** хаба (а не из экземпляра, вызвавшего
`setupOnce()`); без интеграции или без клиента событие возвращается без тегов.
Особенности глобального состояния SDK — в [`testing.md`](testing.md).

## prefer-lowest: что пришлось поднять

Ячейка `--prefer-lowest` (PHP 8.4, Symfony 7.4) падала по двум причинам:

1. **Monolog 2.** `monolog/monolog` не был объявлен — приходил транзитивно через
   `symfony/monolog-bundle` 3.10 → `symfony/monolog-bridge` 5.4 → Monolog 2.3.
   Код целиком на Monolog 3 API (`LogRecord`, `Level`), отсюда fatal
   `SwitchFormatter::format(LogRecord)` vs `format(array)`. Теперь
   `monolog/monolog` в `require` обоих манифестов. Код работает и на 3.0.0, но
   пол — `^3.5`, общий с остальными бандлами (2026-10-04 UTC).
2. **PHPUnit 10.5.** Шаблонный `phpunit.xml.dist` использует
   `<source ignoreIndirectDeprecations>`, которого нет в схеме 10.5 → PHPUnit
   warning → `failOnWarning`. Composer к тому же блокирует 11.0–11.5.49 по
   security advisory (PKSA-z3gr-8qht-p93v). Минимум — `>=11.5.50` в обоих
   манифестах.

## Kernel-тесты на lowest Symfony 7.4: разворот exception handler в `tearDown`

В ячейке `--prefer-lowest` с `7.4.*` `FrameworkBundle::boot()` оставляет свой
exception handler поверх PHPUnit'ового, и `failOnRisky` валит `KernelLoggingTest`
(«did not remove its own exception handlers»). Пин патч-версий `symfony/error-handler`
не помогает. `KernelLoggingTest::tearDown()` запоминает handler в `setUp()` и
вызывает `restore_exception_handler()`, пока верхний handler не совпадёт (лимит 5).
Не удалять при чистке «лишнего» кода под Symfony < 7.4.
Дополнительно `composer-ci.json` (только CI-манифест) объявляет общий для всех бандлов
`conflict` `symfony/error-handler: <7.4.17 || >=8.0,<8.1.5` — в них исправлен оставляемый
`ErrorHandler::register()` exception handler; пин `symfony/*` на `7.4.*` `conflict` не трогает.
Разворот в `tearDown()` остаётся (безвреден).

## `JsonFormatter::collectMetrics()` — тип `array<mixed>`, не `array<string, mixed>`

На level 10 PHPStan видит `normalizeRecord()['context']` как `array<mixed>`
(контекст Monolog допускает int-ключи). Прежняя аннотация `array<string, mixed>`
была ложной; исправлена сигнатура приватного метода (заодно убран бесполезный
сквозной `$extra`), а не добавлен каст/ignore.

## Решения ревью (2026-10-02 UTC) — что отклонено и почему

- **`MetricsShape`** (`@phpstan-type` в `JsonFormatter`): форма метрик точная;
  PHPStan level 10 ловит, например, `id` без `(string)`-каста. Старый словарный
  тип это пропускал.
- **`LoggerIntegrationTest`**: доступ к `Scope::$globalEventProcessors` идёт через
  `globalEventProcessors()`, который при исчезновении поля валит тест с явным
  сообщением вместо `ReflectionException`.
