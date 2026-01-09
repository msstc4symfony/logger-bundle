<?php declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Formatter;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Monolog\LogRecord;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class JsonFormatter extends BaseJsonFormatter
{
    private const DEFAULT_APPLICATION = 'unknown';

    private const DEFAULT_COMPONENT = 'unknown';

    public function __construct(
        int $batchMode = BaseJsonFormatter::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        bool $includeStacktraces = false,
        #[Autowire(param: 'logger_bundle.applicationName')]
        private readonly string $application = self::DEFAULT_APPLICATION,
        #[Autowire(param: 'logger_bundle.componentName')]
        private readonly string $component = self::DEFAULT_COMPONENT,
    ) {
        parent::__construct($batchMode, $appendNewline, $ignoreEmptyContextAndExtra, $includeStacktraces);
    }

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

        [$normalized['metrics'], $normalized['context'], $normalized['extra']] = $this->collectMetrics(
            $normalized['context'],
            $normalized['extra'],
        );

        if ($normalized['context'] === []) {
            $normalized['context'] = new stdClass();
        }

        if ($normalized['extra'] === []) {
            $normalized['extra'] = new stdClass();
        }

        return $this->toJson($normalized, true) . "\n";
    }

    public function formatBatch(array $records): string
    {
        return str_replace("\n\n", "\n", parent::formatBatch($records));
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     *
     * @return array{array<string, mixed>|stdClass, array<string, mixed>, array<string, mixed>}
     */
    private function collectMetrics(array $context, array $extra): array
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

        return [$metrics !== [] ? $metrics : new stdClass(), $context, $extra];
    }
}
