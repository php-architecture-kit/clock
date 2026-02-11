<?php

declare(strict_types=1);

namespace PhpArchitecture\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    private \DateTimeImmutable $frozenAt;

    public function __construct(\DateTimeImmutable $frozenAt)
    {
        $this->frozenAt = $frozenAt;
    }

    public static function at(\DateTimeImmutable $frozenAt): self
    {
        return new self($frozenAt);
    }

    public static function fromNow(): self
    {
        return new self(new \DateTimeImmutable());
    }

    public function now(): \DateTimeImmutable
    {
        return $this->frozenAt;
    }
}
