# php-architecture-kit/clock

PSR-20 Clock implementations for PHP applications. Provides testable time abstractions for domain-driven design and clean architecture.

## Features

- **PSR-20 compliant** - Implements `Psr\Clock\ClockInterface`
- **Testable** - `FrozenClock` for deterministic unit tests
- **Timezone-aware** - `LocalizedClock` for specific timezones
- **Zero dependencies** - Only requires `psr/clock`
- **PHP 7.4+** - Compatible with legacy and modern PHP

## Installation

```bash
composer require php-architecture-kit/clock
```

## Quick Start

```php
use PhpArchitecture\Clock\SystemClock;
use PhpArchitecture\Clock\FrozenClock;
use PhpArchitecture\Clock\LocalizedClock;

// Production: Use system clock
$clock = new SystemClock();
$now = $clock->now(); // DateTimeImmutable

// Testing: Use frozen clock
$clock = FrozenClock::at(new \DateTimeImmutable('2024-06-15 12:00:00'));
$now = $clock->now(); // Always returns '2024-06-15 12:00:00'

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

## Comparison

| Clock | Use Case | Time Changes |
|-------|----------|--------------|
| `SystemClock` | Production | Yes |
| `FrozenClock` | Unit tests | No |
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
