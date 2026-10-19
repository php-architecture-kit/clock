<?php

declare(strict_types=1);

namespace PhpArchitecture\Clock\Tests\Unit;

use PhpArchitecture\Clock\ManualClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class ManualClockTest extends TestCase
{
    #[Test]
    public function implementsClockInterface(): void
    {
        $clock = new ManualClock(new DateTimeImmutable());

        $this->assertInstanceOf(ClockInterface::class, $clock);
    }

    #[Test]
    public function nowIsStableBetweenCalls(): void
    {
        $time = new DateTimeImmutable('2024-06-15 12:00:00');
        $clock = new ManualClock($time);

        $now1 = $clock->now();
        usleep(10000); // 10ms
        $now2 = $clock->now();

        $this->assertEquals($time, $now1);
        $this->assertSame($now1, $now2);
    }

    #[Test]
    public function atFactoryCreatesClockAtGivenTime(): void
    {
        $time = new DateTimeImmutable('2024-01-01 00:00:00');
        $clock = ManualClock::at($time);

        $this->assertEquals($time, $clock->now());
    }

    #[Test]
    public function fromNowFactoryStartsAtCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $clock = ManualClock::fromNow();
        $after = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $clock->now()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $clock->now()->getTimestamp());
    }

    #[Test]
    public function advanceMovesTimeForwardByInterval(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));

        $clock->advance(new DateInterval('PT1H30M'));

        $this->assertEquals(new DateTimeImmutable('2024-06-15 13:30:00'), $clock->now());
    }

    #[Test]
    public function advanceByZeroKeepsTime(): void
    {
        $time = new DateTimeImmutable('2024-06-15 12:00:00');
        $clock = ManualClock::at($time);

        $clock->advance(new DateInterval('PT0S'));

        $this->assertEquals($time, $clock->now());
    }

    #[Test]
    public function advanceSecondsMovesTimeForward(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));

        $clock->advanceSeconds(5);
        $clock->advanceSeconds(90);

        $this->assertEquals(new DateTimeImmutable('2024-06-15 12:01:35'), $clock->now());
    }

    #[Test]
    public function advanceRejectsInvertedInterval(): void
    {
        $interval = new DateInterval('PT5S');
        $interval->invert = 1;
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));

        $this->expectException(InvalidArgumentException::class);

        $clock->advance($interval);
    }

    #[Test]
    public function advanceRejectsNegativeRelativeInterval(): void
    {
        /** @var DateInterval $interval */
        $interval = DateInterval::createFromDateString('-5 seconds');
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));

        $this->expectException(InvalidArgumentException::class);

        $clock->advance($interval);
    }

    #[Test]
    public function rejectedAdvanceKeepsTime(): void
    {
        $time = new DateTimeImmutable('2024-06-15 12:00:00');
        $clock = ManualClock::at($time);

        try {
            $clock->advanceSeconds(-1);
        } catch (InvalidArgumentException) {
        }

        $this->assertEquals($time, $clock->now());
    }

    #[Test]
    public function advanceSecondsRejectsNegativeSeconds(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));

        $this->expectException(InvalidArgumentException::class);

        $clock->advanceSeconds(-5);
    }

    #[Test]
    public function setJumpsForwardAndBackward(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));

        $clock->set(new DateTimeImmutable('2025-01-01 00:00:00'));
        $this->assertEquals(new DateTimeImmutable('2025-01-01 00:00:00'), $clock->now());

        $clock->set(new DateTimeImmutable('2020-01-01 00:00:00'));
        $this->assertEquals(new DateTimeImmutable('2020-01-01 00:00:00'), $clock->now());
    }

    #[Test]
    public function previouslyReturnedTimeIsNotChangedByAdvance(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00'));
        $before = $clock->now();

        $clock->advanceSeconds(60);

        $this->assertEquals(new DateTimeImmutable('2024-06-15 12:00:00'), $before);
    }

    #[Test]
    public function preservesTimezoneWhenAdvancing(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-06-15 12:00:00', new DateTimeZone('Europe/Warsaw')));

        $clock->advance(new DateInterval('P1D'));
        $clock->advanceSeconds(1);

        $this->assertSame('Europe/Warsaw', $clock->now()->getTimezone()->getName());
        $this->assertSame('2024-06-16 12:00:01', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function preservesMicrosecondsWhenAdvancing(): void
    {
        /** @var DateTimeImmutable $time */
        $time = DateTimeImmutable::createFromFormat('U.u', '1718450400.123456');
        $clock = ManualClock::at($time);

        $clock->advanceSeconds(10);

        $this->assertSame('123456', $clock->now()->format('u'));
        $this->assertSame($time->getTimestamp() + 10, $clock->now()->getTimestamp());
    }

    #[Test]
    public function canDriveTimeoutLogic(): void
    {
        $clock = ManualClock::at(new DateTimeImmutable('2024-12-25 10:30:00'));
        $deadline = $clock->now()->modify('+30 seconds');

        $clock->advanceSeconds(29);
        $this->assertFalse($clock->now() >= $deadline);

        $clock->advanceSeconds(1);
        $this->assertTrue($clock->now() >= $deadline);
    }
}
