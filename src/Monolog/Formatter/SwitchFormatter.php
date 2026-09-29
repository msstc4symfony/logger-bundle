<?php declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Monolog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class SwitchFormatter implements FormatterInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.formatter.line')]
        private LineFormatter $humanReadableFormatter,
        private JsonFormatter $logStorageReadableFormatter,
        private RequestStack $requestStack,
        #[Autowire(env: 'default::HUMAN_READABLE')]
        private ?string $humanReadable = null,
    ) {
    }

    #[Override]
    public function format(LogRecord $record): string
    {
        return $this->isHumanOwner()
            ? $this->humanReadableFormatter->format($record)
            : $this->logStorageReadableFormatter->format($record);
    }

    /**
     * @param LogRecord[] $records
     */
    #[Override]
    public function formatBatch(array $records): string
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return implode(PHP_EOL, $records);
    }

    private function isHumanOwner(): bool
    {
        return (PHP_SAPI === 'cli' || !$this->requestStack->getCurrentRequest() instanceof Request)
            && $this->humanReadable !== null
            && $this->humanReadable !== '';
    }
}
