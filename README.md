[![PHP Version Require](http://poser.pugx.org/waffle-commons/event-dispatcher/require/php)](https://packagist.org/packages/waffle-commons/event-dispatcher)
[![PHP CI](https://github.com/waffle-commons/event-dispatcher/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/event-dispatcher/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/event-dispatcher/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/event-dispatcher)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/event-dispatcher/v)](https://packagist.org/packages/waffle-commons/event-dispatcher)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/event-dispatcher/v/unstable)](https://packagist.org/packages/waffle-commons/event-dispatcher)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/event-dispatcher.svg)](https://packagist.org/packages/waffle-commons/event-dispatcher)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/event-dispatcher)](https://github.com/waffle-commons/event-dispatcher/blob/main/LICENSE.md)

Waffle Event Dispatcher Component
=================================

> **Release:** `v0.1.0-beta2` &nbsp;|&nbsp; [`CHANGELOG.md`](./CHANGELOG.md)
> **PSR Compliance:** PSR-14 (`Psr\EventDispatcher\EventDispatcherInterface`, `ListenerProviderInterface`, `StoppableEventInterface`)

A minimal, attribute-driven PSR-14 dispatcher. The dispatcher itself is `final readonly` and stateless; the listener provider stores the listener map and supports priority ordering and `#[AsEventListener]` attribute discovery.

## 📦 Installation

```bash
composer require waffle-commons/event-dispatcher
```

## 🧱 Surface

| Class | Role |
| :--- | :--- |
| `Waffle\Commons\EventDispatcher\Dispatcher\EventDispatcher` | `final readonly` PSR-14 dispatcher. Walks listeners, respects `StoppableEventInterface`. |
| `Waffle\Commons\EventDispatcher\Provider\ListenerProvider` | Listener registry. Manual registration via `addListener()`, or attribute scanning via `register($object)`. |
| `Waffle\Commons\EventDispatcher\Attribute\AsEventListener` | PHP 8 attribute marking a class or method as a listener. |
| `Waffle\Commons\EventDispatcher\Event\AbstractStoppableEvent` | Convenience base implementing `StoppableEventInterface`. |

## 🚀 Manual registration

```php
use Waffle\Commons\EventDispatcher\Dispatcher\EventDispatcher;
use Waffle\Commons\EventDispatcher\Provider\ListenerProvider;

$provider = new ListenerProvider();
$provider->addListener(UserRegistered::class, function (UserRegistered $event): void {
    // …
}, priority: 100); // higher priority = earlier

$dispatcher = new EventDispatcher($provider);
$event = $dispatcher->dispatch(new UserRegistered($userId));
```

## 🏷️ Attribute-driven registration (`#[AsEventListener]`)

The attribute is declared as:

```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class AsEventListener
{
    public function __construct(
        public ?string $event = null,
        public int $priority = 0,
    ) {}
}
```

### Method-level — event class resolved from the parameter type-hint

```php
final class AuditListener
{
    #[AsEventListener(priority: 50)]
    public function onUserRegistered(UserRegistered $event): void
    {
        // resolved automatically from the parameter type
    }
}

$provider->register(new AuditListener());
```

### Class-level — first public non-constructor method is the handler

```php
#[AsEventListener(event: UserRegistered::class, priority: 50)]
final class WelcomeMailer
{
    public function send(UserRegistered $event): void { /* … */ }
}

$provider->register(new WelcomeMailer());
```

## 🛑 Stoppable events

```php
use Waffle\Commons\EventDispatcher\Event\AbstractStoppableEvent;

final class CancellableJob extends AbstractStoppableEvent
{
    public function __construct(public readonly string $jobId) {}
}

$provider->addListener(CancellableJob::class, function (CancellableJob $e): void {
    if ($shouldCancel) {
        $e->stopPropagation();
    }
});
```

The dispatcher honours `isPropagationStopped()` and breaks out of the listener loop.

## 🐘 PHP 8.5 features used

- `final readonly class EventDispatcher` — the dispatcher itself is immutable.
- Constructor property promotion with explicit visibility on listeners.
- Typed properties + parameters throughout.
- Inheritance walks via native `get_parent_class()` (not reflection caches), so listener resolution against parent event types is `O(depth)` without warm-up cost.

## 🧭 Architectural boundary (`mago guard`)

An active dependency **perimeter** is enforced on every CI run by `vendor/bin/mago guard` (bundled into `composer mago`; zero baselines). The rules live in [`mago.toml`](./mago.toml) under `[guard.perimeter]` — a forbidden `use` statement fails the build, not a reviewer.

Production code under `Waffle\Commons\EventDispatcher` may depend **only** on:

- `Waffle\Commons\EventDispatcher\**` — itself
- `Waffle\Commons\Contracts\**` — the shared contracts package, the **only** Waffle dependency permitted
- `Psr\**` — PSR interfaces (PSR-14)
- `@global` + `Psl\**` — PHP core and the PHP Standard Library

Test code under `WaffleTests\Commons\EventDispatcher` is unrestricted (`@all`). Structural rules are guarded too: interfaces must be named `*Interface`, `Exception\**` classes must end in `*Exception`, and any `Enum\**` namespace may hold only `enum` declarations.

Contract-first, component-agnostic by construction: components compose through `waffle-commons/contracts`, never directly through one another.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/event-dispatcher waffle-dev composer tests
```

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
