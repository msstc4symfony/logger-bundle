<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Override;

final class EnvironmentProcessor implements ProcessorInterface
{
    #[Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['sapi'] = PHP_SAPI;
        $record->extra['container_id'] = $this->getContainerId();

        return $record;
    }

    private function getContainerId(): ?string
    {
        static $containerId = null;
        static $containerIdDetected = null;

        if (!$containerIdDetected) {
            if (isset($_ENV['POD_NAME'])) {
                $containerId = $_ENV['POD_NAME'];
            } elseif (isset($_ENV['POD_UID'])) {
                $containerId = $_ENV['POD_UID'];
            } else {
                $containerId = gethostname();
            }

            $containerIdDetected = true;
        }

        return is_string($containerId) ? $containerId : null;
    }
}
