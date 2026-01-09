<?php declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SwitchFormatter implements FormatterInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.formatter.line')]
        private readonly FormatterInterface $humanReadableFormatter,
        private readonly JsonFormatter $logStorageReadableFormatter,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function format(LogRecord $record)
    {
        return $this->isHumanOwner()
            ? $this->humanReadableFormatter->format($record)
            : $this->logStorageReadableFormatter->format($record);
    }

    /**
     * @return string
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return implode(PHP_EOL, $records);
    }

    private function isHumanOwner(): bool
    {
        return (PHP_SAPI === 'cli' || !$this->requestStack->getCurrentRequest() instanceof Request)
            && isset($_ENV['HUMAN_READABLE'])
            && $_ENV['HUMAN_READABLE'] !== '';
    }
}
