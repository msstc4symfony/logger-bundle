<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\DependencyInjection;

/**
 * @internal
 */
final class JsonRecordFields
{
    /** The top-level fields of every JsonFormatter record; an extra key moved there under such a name would replace it. */
    public const ALL = ['message', 'context', 'level', 'level_name', 'channel', 'datetime', 'extra', 'application', 'component', 'metrics'];

    private function __construct()
    {
    }
}
