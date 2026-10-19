# Соглашения

- Раскладка и правила семейства — спек этапа B (`docs/superpowers/specs/2026-10-17-stage-b-conventions-design.md`
  в корне msstc4symfony): канонические слои, `@internal` вне Public API (только в мажорах), `#[\Override]` везде.

## Идиомы PHP

Бандл требует PHP >= 8.2 (с 1.1.0): синтаксис только 8.2, функции 8.4 — через `symfony/polyfill-php84`.

- **`#[Override]`** — на каждом методе, переопределяющем родителя/интерфейс
  (все процессоры, форматтеры, `ExceptionFilterDecorator`,
  `LoggerBundle`, `AddExceptionFilterPass`).
- **Константы без нативного типа** (PHP 8.2): `private const DEFAULT_APPLICATION` в `JsonFormatter`.
- **`readonly`** на промотированных свойствах конструктора там, где состояние
  не меняется после создания (`UserProcessor`, `WebProcessor`,
  `SwitchFormatter`, `ExceptionFilterDecorator`). `EnvironmentProcessor`
  тоже `final readonly class`, но его `$containerId` — не промотированное
  свойство: оно объявлено отдельно и вычисляется в теле конструктора из
  необязательных параметров `$podName`/`$podUid`, которые сами по себе не
  промотированы.
- **`array_any()`** (PHP 8.4, полифилл на 8.2/8.3) в `ExceptionFilterDecorator::mustSkip()`
  вместо `array_filter`/`in_array`-обвязки.
- **First-class callable синтаксис**: `array_filter($_SERVER['argv'], is_string(...))`
  в `ConsoleProcessor`.

Асимметричная видимость и property hooks в этом бандле **не используются** —
в отличие от `healthcheck-bundle`, здесь нет DTO-аккумуляторов, для которых
они были бы уместны. Не переносить их сюда бездумно по аналогии с
соседним бандлом.

## Файл/неймспейс

- PSR-4 root: `Msstc4Symfony\LoggerBundle\` → `src/`.
- Тестовый PSR-4 root: `Msstc4Symfony\LoggerBundle\Test\` → `tests/` (наборы `tests/Unit`,
  `tests/Integration` — раскладка общая для всех бандлов `bundle-standard`).
- Один класс на файл. Классы `final` (или `final readonly`, если все
  свойства immutable).

## Заголовок файла

Двухстрочный `<?php` + `declare(strict_types=1);` — стандарт проекта.
Но `.php-cs-fixer.dist.php` явно выключает `linebreak_after_opening_tag` и
`blank_line_after_opening_tag`, поэтому cs-fixer это не нормализует сам:
`src/Framework/Monolog/Formatter/JsonFormatter.php` и `SwitchFormatter.php` остались
на однострочном `<?php declare(strict_types=1);`. При правке этих файлов не
нужно "чинить" их формат отдельным PR — это не баг, а нетронутый cs-fixer'ом
кусок; но новые файлы пиши двухстрочным вариантом.

## Комментарии

Только на английском, минимум — комментировать **почему**, а не **что**.
Пример из кода: `// prevents infinite recursion with chained objects` в
`ExceptionContextProcessor::prepareException()` — объясняет неочевидную
причину (`spl_object_hash`-детект цикла), а не пересказывает код.

## PHPStan baseline

`phpstan-baseline.neon` сейчас пуст (`parameters.ignoreErrors: []`) на обоих
манифестах — Task 9 добилась этого, убрав `phpstan/phpstan-doctrine` и
сделав `sentry/sentry` резолвящимся на обоих путях. Baseline **не** растёт
для нового кода: `make check` должен падать на новых находках, а не
проглатывать их. `make regenerate-baseline` — только для осознанного
принятия дрейфа, не как рутинная операция после правки.
