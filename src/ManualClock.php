<?php

declare(strict_types=1);

namespace PhpArchitecture\Clock;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * Time moves only when told to: forward with advance()/advanceSeconds(), anywhere with set().
 */
final class ManualClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(DateTimeImmutable $now)
    {
        $this->now = $now;
    }

    public static function at(DateTimeImmutable $now): self
    {
        return new self($now);
    }

    public static function fromNow(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * @throws InvalidArgumentException when the interval would move time backwards
     */
    public function advance(DateInterval $interval): void
    {
        $next = $this->now->add($interval);

        if ($next < $this->now) {
            throw new InvalidArgumentException('ManualClock can only advance forward; use set() to move time backwards.');
        }

        $this->now = $next;
    }

    /**
     * @throws InvalidArgumentException when $seconds is negative
     */
    public function advanceSeconds(int $seconds): void
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(sprintf('ManualClock can only advance forward, %d seconds given; use set() to move time backwards.', $seconds));
        }

        $this->now = $this->now->add(new DateInterval(sprintf('PT%dS', $seconds)));
    }

    public function set(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
