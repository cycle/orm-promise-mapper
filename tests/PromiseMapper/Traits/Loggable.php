<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Traits;

use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\PromiseMapper\Tests\Utils\TestLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

trait Loggable
{
    public static LoggerInterface $logger;

    protected function setUpLogger(DriverInterface $driver)
    {
        static::$logger = static::$logger ?? new TestLogger();

        if ($driver instanceof LoggerAwareInterface) {
            $driver->setLogger(static::$logger);
        }

        return $this;
    }

    protected function enableProfiling(): void
    {
        static::$logger->enable();
    }

    protected function disableProfiling(): void
    {
        static::$logger->disable();
    }
}
