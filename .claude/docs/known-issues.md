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

## `.github/workflows/checks.yml` устарел — CI не отражает реальные требования

Workflow ставит PHP 8.1 и запускает `make check`/`phpunit` как отдельный
job, хотя `composer.json` требует `"php": ">=8.4"`. Он также не использует
reusable workflow `bundle-standard/.github/workflows/php-bundle.yml` — то
есть красная или бессмысленно-зелёная сборка здесь ожидаема и не сигнализирует
о регрессии в коде. Исправление — Task 10, намеренно отложенная до публикации
тега `bundle-standard@v1` (на момент этой документации тег не существует —
`git -C bundle-standard tag -l` пуст). Task 10 ещё не выполнялась в этой
ветке, поэтому результатов по красным ячейкам целевой матрицы (PHP 8.4/8.5 ×
Symfony 6.4/7.4/8.x) пока не существует — записывать их будет отчёт Task 10.
Подробности целевой модели — `ci.md`.

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

## `src/Sentry/Integration/LoggerIntegration.php` не покрыт тестами

Файл вообще не имеет юнит-тестов. Task 9 поменял в нём
`$integration instanceof IntegrationInterface` на `instanceof self`
(исправление статически некорректного сужения типа — старая проверка была
no-op, потому что значение уже было типизировано `?IntegrationInterface`) —
без единого теста, который поймал бы регрессию. При следующей правке этого
файла стоит сначала добавить тест на `setupOnce()` (например, через
`SentrySdk`/`Scope`-моки или интеграционный прогон с реальным SDK), а не
полагаться на PHPStan — статический анализ этот класс no-op-сужений типов
не ловит.
