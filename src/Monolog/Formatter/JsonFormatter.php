<?php declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Monolog\Formatter;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Monolog\LogRecord;
use Override;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class JsonFormatter extends BaseJsonFormatter
{
    private const string DEFAULT_APPLICATION = 'unknown';

    private const string DEFAULT_COMPONENT = 'unknown';

    public function __construct(
        int $batchMode = BaseJsonFormatter::BATCH_MODE_JSON,
        bool $appendNewline = false,
        bool $ignoreEmptyContextAndExtra = false,
        bool $includeStacktraces = false,
        #[Autowire(param: 'logger_bundle.applicationName')]
        private readonly string $application = self::DEFAULT_APPLICATION,
        #[Autowire(param: 'logger_bundle.componentName')]
        private readonly string $component = self::DEFAULT_COMPONENT,
    ) {
        parent::__construct($batchMode, $appendNewline, $ignoreEmptyContextAndExtra, $includeStacktraces);
    }

    #[Override]
    public function format(LogRecord $record): string
    {
        $normalized = $this->normalizeRecord($record);

        $normalized['application'] = $this->application;
        $normalized['component'] = $this->component;

        if (!isset($normalized['context']) || !is_array($normalized['context'])) {
            $normalized['context'] = [];
        }

        if (!isset($normalized['extra']) || !is_array($normalized['extra'])) {
            $normalized['extra'] = [];
        }

        [$normalized['metrics'], $normalized['context']] = $this->collectMetrics($normalized['context']);

        if ($normalized['context'] === []) {
            $normalized['context'] = new stdClass();
        }

        if ($normalized['extra'] === []) {
            $normalized['extra'] = new stdClass();
        }

        return $this->toJson($normalized, true) . ($this->appendNewline ? "\n" : '');
    }

    #[Override]
    public function formatBatch(array $records): string
    {
        return str_replace("\n\n", "\n", parent::formatBatch($records));
    }

    /**
     * @param array<mixed> $context
     *
     * @return array{array<'count'|'size'|'duration'|'id'|'status', int|float|string>|stdClass, array<mixed>}
     */
    private function collectMetrics(array $context): array
    {
        $metrics = [];

        foreach (['count', 'size', 'duration'] as $key) {
            if (isset($context[$key]) && is_scalar($context[$key])) {
                $metrics[$key] = is_int($context[$key]) ? $context[$key] : (float) $context[$key];
                unset($context[$key]);
            }
        }

        foreach (['id', 'status'] as $key) {
            if (isset($context[$key]) && is_scalar($context[$key])) {
                $metrics[$key] = (string) $context[$key];
                unset($context[$key]);
            }
        }

        return [$metrics !== [] ? $metrics : new stdClass(), $context];
    }
}
