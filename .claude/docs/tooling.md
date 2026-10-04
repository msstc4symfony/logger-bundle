# Инструментарий

## Гейт `make check`

Последовательно, любой ненулевой код завершает гейт:

| Шаг | Инструмент | Конфиг |
|---|---|---|
| `php -l` | синтаксис | — |
| PHPStan | level 10, `--memory-limit=512M` | `phpstan.dist.neon` (локально) / `phpstan-ci.neon` (CI, через `PHPSTAN_CONFIG`) |
| PHP-CS-Fixer | check-режим | `.php-cs-fixer.dist.php` |
| `composer validate --strict --no-check-publish` | манифест | — |
| `composer audit` | security advisories | — |
| Rector | dry-run (`-n`) | `rector.php` |
| deptrac | правила слоёв | `deptrac.yaml` |

`make check` **не проходит end-to-end на чистой установке `composer.json`**,
потому что `deptrac/deptrac` объявлен только в `composer-ci.json` — на
плоском манифесте `vendor/bin/deptrac` просто отсутствует. Это свойство
двухманифестного дизайна, не дефект (у `healthcheck-bundle` то же самое).
Полный локальный прогон:

```bash
COMPOSER=composer-ci.json composer install
PHPSTAN_CONFIG=phpstan-ci.neon make check
make test
```

## Два манифеста

- `composer.json` — публикуемый на Packagist. `require-dev` включает
  `sentry/sentry` (см. ниже — не убирать).
- `composer-ci.json` — тот же runtime plus полный набор опциональных
  dev-зависимостей для CI: `deptrac/deptrac`, `infection/infection`,
  `roave/backward-compatibility-check`, и тоже `sentry/sentry`.

**Оба манифеста ставятся в один и тот же `vendor/`.** Если проверяешь
поведение, зависящее от того, какой манифест реально установлен —
сначала `rm -rf vendor/`, иначе можно получить зелёный результат по
неверной причине (это уже стоило одного цикла ревью в Task 9).

## `sentry/sentry` — почему он в `require-dev` обоих манифестов, а не только в CI

`LoggerIntegration` делает `implements Sentry\Integration\IntegrationInterface`.
PHPStan ядро жёстко помечает "implements unknown interface"
(`interface.notFound`) как `nonIgnorable()` — эта находка **не может** быть
занесена в baseline ни при каком конфиге, в отличие от обычного
`class.notFound` для символов, использованных только в сигнатурах методов
(как у остальных опциональных зависимостей в этом семействе бандлов).
Поэтому на плоском `composer.json`, где `sentry/sentry` отсутствовал бы,
PHPStan не мог бы стать зелёным никаким baseline-приёмом — только реальным
резолвом интерфейса. Отсюда решение: `sentry/sentry` живёт в `require-dev`
**обоих** манифестов. Для потребителей пакета это ничего не стоит —
`require-dev` не ставится при `composer require`.

Не "прибирать" это обратно в `composer-ci.json` без понимания этой причины
— она удалена намеренно и обоснованно в Task 9 (коммит `5ad60e7`).

## `phpstan/phpstan-doctrine` — почему его больше нет

Плагин был скопирован из `healthcheck-bundle` (там он используется по
делу — есть Doctrine-код), но в `logger-bundle` Doctrine-кода нет вообще.
`EntityNotFinalRule` плагина вызывает нативный `class_exists()` на каждом
`final`-классе — с отсутствующим (на плоском манифесте) интерфейсом Sentry
это приводило к фатальной ошибке PHP при компиляции `LoggerIntegration`
(не PHPStan-находка, а падение движка, которое `make regenerate-baseline`
не мог даже записать). Убран из обоих манифестов в Task 9 (коммит `5ad60e7`).
Не возвращать без Doctrine-кода в бандле.

## PHPStan baseline

`phpstan-baseline.neon` пуст (`parameters.ignoreErrors: []`) на обоих
манифестах после Task 9. Новые находки не баслайнятся автоматически —
`make check` падает. `make regenerate-baseline` — только для осознанного
принятия находок.

`phpstan-ci.neon` расширяет `phpstan.dist.neon` и добавляет
`reportUnmatchedIgnoredErrors: false` — на случай, если в CI-манифесте
резолвятся классы, из-за которых локальный baseline (для других бандлов
семейства — обычно непустой) содержит неактуальные записи. У `logger-bundle`
сейчас baseline пуст, так что эта настройка сейчас не absorbing ничего
реального, но остаётся частью стандартного шаблона.

## deptrac

`deptrac.yaml` — 4 слоя: `Monolog` (ядро, ни от чего не зависит), `Sentry`
(самодостаточен), `DependencyInjection` (зависит от `Monolog`),
`BundleRoot` (зависит от `DependencyInjection`). Правила из Task 9, не
менять без структурной причины — проще отрефакторить зависимость, чем
ослаблять правило.
