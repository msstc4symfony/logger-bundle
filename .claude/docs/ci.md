# CI

## Текущее состояние (устарело, не трогать здесь — см. Task 10)

`.github/workflows/checks.yml` сейчас — самостоятельный однопроходный job:
PHP 8.1, `composer install` из `composer.json`, `make check` + `phpunit` с
покрытием на Codecov v5. Он **не** использует reusable workflow и
рассинхронизирован с манифестом (`"php": ">=8.4"` в `composer.json`).
Правка этого файла — предмет отдельной задачи (Task 10), отложенной до
публикации тега `bundle-standard@v1`; в рамках документационной задачи файл
намеренно не менялся. См. `known-issues.md`.

## Целевая модель — reusable workflow из `bundle-standard`

`bundle-standard/.github/workflows/php-bundle.yml` — переиспользуемый
workflow (`on: workflow_call`), который Task 10 подключит так:

```yaml
jobs:
  standard:
    uses: msstc4symfony/bundle-standard/.github/workflows/php-bundle.yml@v1
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

**Менять матрицу нужно через входы `with:` в `checks.yml` бандла, а не
форком workflow.** Форк ломает единый источник правды по всему семейству
бандлов — любая правка потом расходится вручную по каждому репозиторию.

`logger-bundle` не требует композитных дополнительных зависимостей
(предполагаемое значение `extensions` — стандартный список плюс `json`,
поскольку `ext-json` — часть `require` в `composer.json`).

## После подключения (когда Task 10 выполнится)

Локальных джобов в `checks.yml` не останется — весь пайплайн живёт в
`bundle-standard`. Изменения в логике самого гейта (новый шаг проверки,
новый инструмент) вносятся в `bundle-standard`, а не копированием сюда.
