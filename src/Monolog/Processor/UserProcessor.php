<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final readonly class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private ?TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        $token = $this->tokenStorage?->getToken();

        if ($token instanceof TokenInterface) {
            $record->extra['user_id'] = $token->getUserIdentifier();
        }

        return $record;
    }
}
