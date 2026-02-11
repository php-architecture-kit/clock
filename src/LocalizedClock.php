<?php

declare(strict_types=1);

namespace PhpArchitecture\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

final class LocalizedClock implements ClockInterface
{
    private \DateTimeZone $timeZone;

    public function __construct(\DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    public static function utc(): self
    {
        return new self(new \DateTimeZone('UTC'));
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timeZone);
    }
}
