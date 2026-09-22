<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class EnvironmentProcessor implements ProcessorInterface
{
    private ?string $containerId;

    public function __construct(
        #[Autowire(env: 'default::POD_NAME')]
        ?string $podName = null,
        #[Autowire(env: 'default::POD_UID')]
        ?string $podUid = null,
    ) {
        $hostname = gethostname();

        $this->containerId = match (true) {
            $podName !== null && $podName !== '' => $podName,
            $podUid !== null && $podUid !== '' => $podUid,
            is_string($hostname) && $hostname !== '' => $hostname,
            default => null,
        };
    }

    #[Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['sapi'] = PHP_SAPI;
        $record->extra['container_id'] = $this->containerId;

        return $record;
    }
}
