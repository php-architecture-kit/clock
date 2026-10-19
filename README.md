# php-architecture-kit/clock

PSR-20 Clock implementations for PHP applications. Provides testable time abstractions for domain-driven design and clean architecture.

## Features

- **PSR-20 compliant** - Implements `Psr\Clock\ClockInterface`
- **Testable** - `FrozenClock` for deterministic unit tests, `ManualClock` when time has to move
- **Timezone-aware** - `LocalizedClock` for specific timezones
- **Zero dependencies** - Only requires `psr/clock`
- **PHP 8.0+**

## Installation

```bash
composer require php-architecture-kit/clock
```

## Quick Start

```php
use PhpArchitecture\Clock\SystemClock;
use PhpArchitecture\Clock\FrozenClock;
use PhpArchitecture\Clock\LocalizedClock;
use PhpArchitecture\Clock\ManualClock;

// Production: Use system clock
$clock = new SystemClock();
$now = $clock->now(); // DateTimeImmutable

// Testing: Use frozen clock
$clock = FrozenClock::at(new \DateTimeImmutable('2024-06-15 12:00:00'));
$now = $clock->now(); // Always returns '2024-06-15 12:00:00'

// Testing timeouts or simulations: Use manual clock
$clock = ManualClock::at(new \DateTimeImmutable('2024-06-15 12:00:00'));
$clock->advanceSeconds(30);
$now = $clock->now(); // '2024-06-15 12:00:30'

// Timezone-specific: Use localized clock
$clock = new LocalizedClock(new \DateTimeZone('Europe/Warsaw'));
$now = $clock->now(); // DateTimeImmutable in Warsaw timezone

// UTC shortcut
$clock = LocalizedClock::utc();
```

## Clock Implementations

### SystemClock

Returns the current system time. Use in production code.

```php
$clock = new SystemClock();
$now = $clock->now(); // Current time
```

### FrozenClock

Returns a fixed time. Ideal for unit testing.

```php
// Freeze at specific time
$clock = FrozenClock::at(new \DateTimeImmutable('2024-01-01 00:00:00'));

// Freeze at current time
$clock = FrozenClock::fromNow();

// Time never changes
$clock->now(); // Always the same
usleep(10000);
$clock->now(); // Still the same
```

### ManualClock

Returns a fixed time that moves only when told to. Use it in tests of timeouts and expiry, or to drive a simulation step by step.

```php
$clock = ManualClock::at(new \DateTimeImmutable('2024-01-01 00:00:00'));
// or: ManualClock::fromNow();

$clock->now();                                  // '2024-01-01 00:00:00', until moved

$clock->advanceSeconds(90);                     // '2024-01-01 00:01:30'
$clock->advance(new \DateInterval('P1D'));      // '2024-01-02 00:01:30'
$clock->set(new \DateTimeImmutable('2023-12-31 23:59:59')); // jump anywhere, also back

$clock->advanceSeconds(-1);                     // InvalidArgumentException
```

`advance()` and `advanceSeconds()` only move time forward - a negative step throws, because timeout and expiry logic usually assumes time never goes back. Moving back is possible only explicitly, with `set()`. The timezone and microseconds of the initial time are kept.

Unlike `FrozenClock`, a `ManualClock` is mutable: every service holding the same instance sees the new time.

### LocalizedClock

Returns current time in a specific timezone.

```php
// Any timezone
$clock = new LocalizedClock(new \DateTimeZone('America/New_York'));

// UTC shortcut
$clock = LocalizedClock::utc();

$now = $clock->now();
echo $now->getTimezone()->getName(); // 'America/New_York' or 'UTC'
```

## Usage in Domain Services

Inject `ClockInterface` instead of calling `new \DateTimeImmutable()` directly:

```php
use Psr\Clock\ClockInterface;

class OrderService
{
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function createOrder(array $items): Order
    {
        return new Order(
            items: $items,
            createdAt: $this->clock->now()
        );
    }
}
```

### Production Configuration

```php
// Symfony
services:
    Psr\Clock\ClockInterface:
        class: PhpArchitecture\Clock\SystemClock

// Laravel
$this->app->bind(ClockInterface::class, SystemClock::class);
```

### Testing

```php
class OrderServiceTest extends TestCase
{
    public function testOrderCreatedWithCorrectTimestamp(): void
    {
        $fixedTime = new \DateTimeImmutable('2024-06-15 12:00:00');
        $clock = FrozenClock::at($fixedTime);
        
        $service = new OrderService($clock);
        $order = $service->createOrder(['item1', 'item2']);
        
        $this->assertEquals($fixedTime, $order->getCreatedAt());
    }
}
```

### Testing a Timeout

```php
class ReservationTest extends TestCase
{
    public function testReservationExpiresAfterFifteenMinutes(): void
    {
        $clock = ManualClock::at(new \DateTimeImmutable('2024-06-15 12:00:00'));
        $reservation = new Reservation($clock);

        $clock->advanceSeconds(14 * 60);
        $this->assertFalse($reservation->isExpired());

        $clock->advanceSeconds(60);
        $this->assertTrue($reservation->isExpired());
    }
}
```

## Comparison

| Clock | Use Case | Time Changes |
|-------|----------|--------------|
| `SystemClock` | Production | Yes |
| `FrozenClock` | Unit tests | No |
| `ManualClock` | Timeout tests, simulations | When told to |
| `LocalizedClock` | Timezone-specific apps | Yes |

## API Reference

### SystemClock

| Method | Description |
|--------|-------------|
| `now(): DateTimeImmutable` | Returns current system time |

### FrozenClock

| Method | Description |
|--------|-------------|
| `__construct(DateTimeImmutable $frozenAt)` | Create with fixed time |
| `at(DateTimeImmutable $frozenAt): self` | Factory: create with fixed time |
| `fromNow(): self` | Factory: freeze current time |
| `now(): DateTimeImmutable` | Returns the frozen time |

### ManualClock

| Method | Description |
|--------|-------------|
| `__construct(DateTimeImmutable $now)` | Create at given time |
| `at(DateTimeImmutable $now): self` | Factory: create at given time |
| `fromNow(): self` | Factory: start at current time |
| `now(): DateTimeImmutable` | Returns the current manual time |
| `advance(DateInterval $interval): void` | Moves time forward; throws `InvalidArgumentException` for a negative interval |
| `advanceSeconds(int $seconds): void` | Moves time forward by seconds; throws `InvalidArgumentException` for negative seconds |
| `set(DateTimeImmutable $now): void` | Jumps to the given time, forward or backward |

### LocalizedClock

| Method | Description |
|--------|-------------|
| `__construct(DateTimeZone $timeZone)` | Create with timezone |
| `utc(): self` | Factory: create UTC clock |
| `now(): DateTimeImmutable` | Returns current time in timezone |

## Testing

Package is tested with PHPUnit in the [php-architecture-kit/workspace](https://github.com/php-architecture-kit/workspace) project. 

## License

MIT
