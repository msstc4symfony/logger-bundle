<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class WebProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    #[Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return $record;
        }

        $record->extra['url'] = $request->getUri();

        if ($request->getClientIp() !== null) {
            $record->extra['ip'] = $request->getClientIp();
        }
        $record->extra['http_method'] = $request->getMethod();

        return $record;
    }
}
