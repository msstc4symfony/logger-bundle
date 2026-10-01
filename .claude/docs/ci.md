# CI

## Модель — reusable workflow из `bundle-standard`

`bundle-standard/.github/workflows/php-bundle.yml` — переиспользуемый
workflow (`on: workflow_call`), подключённый в `checks.yml` так:

```yaml
jobs:
  standard:
    uses: msstc4symfony/bundle-standard/.github/workflows/php-bundle.yml@v1.6.2
    with:
      slug: msstc4symfony/logger-bundle
      extensions: 'mbstring, xml, ctype, iconv, intl, json'
    secrets:
      CODECOV_TOKEN: ${{ secrets.CODECOV_TOKEN }}
```

Входы `php-bundle.yml` (см. `bundle-standard/README.md`):

| Вход | Дефолт | Назначение |
|---|---|---|
| `slug` | — (обязателен) | `owner/repo` для загрузки в Codecov |
| `php-versions` | `["8.4","8.5"]` | матрица PHP для PHPUnit |
| `symfony-versions` | 6.4 / 7.4 / 8.x, coverage на 8.x | матрица Symfony, объект `{version,label,codecov}` |
| `extensions` | `mbstring, xml, ctype, iconv, intl` | PHP-расширения для тестов |
| `run-deptrac` | `true` | джоба DEPTRAC |
| `run-infection` | `true` | mutation testing (только push в `main`) |
| `run-bc-check` | `true` | Roave backward-compatibility check |
| `run-codecov` | `false` | загрузка покрытия в Codecov; нужен секрет `CODECOV_TOKEN` |

**Менять матрицу нужно через входы `with:` в `checks.yml` бандла, а не
форком workflow.** Форк ломает единый источник правды по всему семейству
бандлов — любая правка потом расходится вручную по каждому репозиторию.

`logger-bundle` не требует композитных дополнительных зависимостей
(предполагаемое значение `extensions` — стандартный список плюс `json`,
поскольку `ext-json` — часть `require` в `composer.json`).

## Где менять гейт

Локальных джобов в `checks.yml` не останется — весь пайплайн живёт в
`bundle-standard`. Изменения в логике самого гейта (новый шаг проверки,
новый инструмент) вносятся в `bundle-standard`, а не копированием сюда.

Версия стандарта закреплена точным тегом (`@v1.6.2`): GitHub не понимает
диапазоны, обновление стандарта — явная правка этой строки.

Codecov выключен (`run-codecov` по умолчанию `false`): в организации
`msstc4symfony` пока нет секрета `CODECOV_TOKEN`. Включение — `run-codecov: true`
в `with:` после появления секрета.
