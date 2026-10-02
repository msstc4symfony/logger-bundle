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

## `v1.1.0` — BC-слом без смены мажора (осознанно)

`v1.0.x` жили под `MaxShamaev\LoggerBundle`, `v1.1.0` — под `Msstc4Symfony\LoggerBundle`
и другим именем пакета. По semver это мажор, но владелец решил держать линейку на `1.x`:
под именем `msstc4symfony/logger-bundle` старых версий нет, потребителей ломать некому.
BC check (Roave) сравнивает с последним тегом, поэтому с `v1.1.0` он снова зелёный.

## `symfony/monolog-bundle` — `^3.10|^4.0`, не `^3.0`

monolog-bundle 3.x поддерживает Symfony максимум 7.4; свежий Symfony 8 ставит 4.x.
С `^3.0` бандл не устанавливался в проект на Symfony 8, а CI этого не видел: ячейка
«Symfony 8» фиксировала только framework-bundle/console, и Composer оставлял
`http-kernel` и остальное на 7.4. С `bundle-standard` v1.3.1 (фиксация http-kernel/DI/config + проверка) такая ячейка падает
(шаг «Assert the matrix Symfony version was installed»).

## `symfony/yaml` — обязательная зависимость

`LoggerExtension` грузит `services.yaml` через `YamlFileLoader`, но до `v1.1.1`
`symfony/yaml` не был объявлен: приложение без него падало при сборке контейнера,
а локально и в CI пакет приходил транзитивно (deptrac). С `bundle-standard` v1.5.0
верификатор требует `symfony/yaml`, если `src/` использует `YamlFileLoader`.

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

## `LoggerIntegration` покрыт тестами (с v1.2.0)

До v1.2.0 у `src/Sentry/Integration/LoggerIntegration.php` не было тестов.
Теперь `LoggerIntegrationTest` проверяет: теги `application`/`component`
ставятся; ровно один глобальный процессор; значения берутся из интеграции,
зарегистрированной в клиенте **текущего** хаба (а не из экземпляра, вызвавшего
`setupOnce()`); без интеграции или без клиента событие возвращается без тегов.
Особенности глобального состояния SDK — в [`testing.md`](testing.md).

## `JsonFormatter` без перевода строки склеивал записи в одну строку (до v1.1.2)

`Monolog\Formatter\JsonFormatter` по умолчанию `appendNewline = false`, а наш
`services.yaml` биндил только `$batchMode`. В stream-хендлерах (CLI, Messenger-воркеры)
записи писались подряд `…}{"message"…` — Loki/Alloy и любой line-based шиппер
видят одну гигантскую строку. Под RoadRunner (`logs.mode: raw`) баг маскировался:
RR сам режет вывод воркера. Исправлено аргументом `$appendNewline: true` у сервиса
`JsonFormatter` в `services.yaml` (точечно, не `_defaults.bind`) — дефолт конструктора
не трогали, т.к. смена default value параметра = BC-break для Roave. Покрыто
`KernelLoggingTest::testJsonFormatterWritesOneRecordPerLine` (через реальный сервис).
Заодно `SwitchFormatter::formatBatch` теперь делегирует `formatBatch` выбранного
форматтера: раньше он склеивал `format()` через `PHP_EOL`, и между записями batch
появлялась пустая строка (у LineFormatter — всегда).

## prefer-lowest: что пришлось поднять (v1.2.0, bundle-standard v1.8.0)

Ячейка `--prefer-lowest` (PHP 8.4, Symfony 6.4) падала по четырём причинам:

1. **Monolog 2.** `monolog/monolog` не был объявлен — приходил транзитивно через
   `symfony/monolog-bundle` 3.10 → `symfony/monolog-bridge` 5.4 → Monolog 2.3.
   Код целиком на Monolog 3 API (`LogRecord`, `Level`), отсюда fatal
   `SwitchFormatter::format(LogRecord)` vs `format(array)`. Теперь
   `monolog/monolog: ^3.0` в `require` обоих манифестов. Реальный минимум
   проверен: весь suite зелёный на 3.0.0 (3.5 не нужен).
2. **PHPUnit 10.5.** Шаблонный `phpunit.xml.dist` использует
   `<source ignoreIndirectDeprecations>`, которого нет в схеме 10.5 → PHPUnit
   warning → `failOnWarning`. Composer к тому же блокирует 11.0–11.5.49 по
   security advisory (PKSA-z3gr-8qht-p93v). Минимум — `>=11.5.50` в обоих
   манифестах.
3. **`RequestStack([$request])`** в `WebProcessorTest` — конструктор с
   запросами появился в Symfony 7.2; на 6.4 стек пустой. Тест переведён на
   `push()` в хелпере — прямой `push()` рядом с `new` Rector откатывает
   (см. [`testing.md`](testing.md)).
4. **`symfony/error-handler` < 6.4.44 (и 7.0–7.4.16)** оставляет
   зарегистрированный exception handler, когда ошибками уже управляет кто-то
   другой (PHPUnit) — `FrameworkBundle::boot()` → `ErrorHandler::register()`;
   в `KernelLoggingTest` это `Risky: Test code or tested code did not remove its
   own exception handlers`. Плюс 6.4.0 на PHP 8.4 даёт `E_STRICT is deprecated`.
   Исправлено в 6.4.44 / 7.4.17 (8.x не затронут). Компонент не листится в
   нашем `require`, а шаг стандарта «Pin Symfony version» переписал бы любой
   констрейнт `symfony/*` на `6.4.*`, поэтому минимум задан `conflict`-ом
   **только в `composer-ci.json`**: в рантайме потребителей это не баг
   (проявляется только под PHPUnit), навязывать им конфликт незачем.

Воспроизведение локально: копия репо в `$TMPDIR`, `COMPOSER=composer-ci.json`,
`composer remove --dev --no-update roave/backward-compatibility-check deptrac/deptrac`,
`composer require --no-update symfony/{framework-bundle,console,http-kernel,dependency-injection,config,security-bundle,yaml}:6.4.*`,
`composer update --prefer-lowest --prefer-stable`, `vendor/bin/phpunit`.

## `JsonFormatter::collectMetrics()` — тип `array<mixed>`, не `array<string, mixed>`

На level 10 PHPStan видит `normalizeRecord()['context']` как `array<mixed>`
(контекст Monolog допускает int-ключи). Прежняя аннотация `array<string, mixed>`
была ложной; исправлена сигнатура приватного метода (заодно убран бесполезный
сквозной `$extra`), а не добавлен каст/ignore.
