<?php

declare(strict_types=1);

namespace Hypervel\Support\Testing\Fakes;

use Closure;
use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Stringable\Str;
use Hyperf\Support\Traits\ForwardsCalls;
use Hypervel\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionFunction;

class EventFake implements Fake, EventDispatcherInterface
{
    use ForwardsCalls;
    use ReflectsClosures;

    /**
     * The original event dispatcher.
     */
    protected EventDispatcherInterface $dispatcher;

    /**
     * The event types that should be intercepted instead of dispatched.
     */
    protected array $eventsToFake = [];

    /**
     * The event types that should be dispatched instead of intercepted.
     */
    protected array $eventsToDispatch = [];

    /**
     * All of the events that have been intercepted keyed by type.
     */
    protected array $events = [];

    /**
     * Create a new event fake instance.
     */
    public function __construct(EventDispatcherInterface $dispatcher, array|string $eventsToFake = [])
    {
        $this->dispatcher = $dispatcher;
        $this->eventsToFake = Arr::wrap($eventsToFake);
    }

    /**
     * Specify the events that should be dispatched instead of faked.
     */
    public function except(array|string $eventsToDispatch): static
    {
        $this->eventsToDispatch = array_merge(
            $this->eventsToDispatch,
            Arr::wrap($eventsToDispatch)
        );

        return $this;
    }

    /**
     * Assert if an event has a listener attached to it.
     */
    public function assertListening(string $expectedEvent, string $expectedListener): void
    {
        /* @phpstan-ignore-next-line */
        foreach ($this->dispatcher->getListeners($expectedEvent) as $listenerClosure) {
            $actualListener = (new ReflectionFunction($listenerClosure))
                ->getStaticVariables()['listener'];

            if (is_string($actualListener) && Str::endsWith($actualListener, '@handle')) {
                $actualListener = Str::parseCallback($actualListener)[0];
            }

            if ($actualListener === $expectedListener
                || ($actualListener instanceof Closure
                && $expectedListener === Closure::class)) {
                PHPUnit::assertTrue(true);

                return;
            }
        }

        PHPUnit::assertTrue(
            false,
            sprintf(
                'Event [%s] does not have the [%s] listener attached to it',
                $expectedEvent,
                print_r($expectedListener, true)
            )
        );
    }

    /**
     * Assert if an event was dispatched based on a truth-test callback.
     */
    public function assertDispatched(Closure|string $event, callable|int|null $callback = null): void
    {
        if ($event instanceof Closure) {
            [$event, $callback] = [$this->firstClosureParameterType($event), $event];
        }

        if (is_int($callback)) {
            $this->assertDispatchedTimes($event, $callback);
            return;
        }

        PHPUnit::assertTrue(
            $this->dispatched($event, $callback)->count() > 0,
            "The expected [{$event}] event was not dispatched."
        );
    }

    /**
     * Assert if an event was dispatched a number of times.
     */
    public function assertDispatchedTimes(string $event, int $times = 1): void
    {
        $count = $this->dispatched($event)->count();

        PHPUnit::assertSame(
            $times,
            $count,
            "The expected [{$event}] event was dispatched {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if an event was dispatched based on a truth-test callback.
     */
    public function assertNotDispatched(Closure|string $event, ?callable $callback = null): void
    {
        if ($event instanceof Closure) {
            [$event, $callback] = [$this->firstClosureParameterType($event), $event];
        }

        PHPUnit::assertCount(
            0,
            $this->dispatched($event, $callback),
            "The unexpected [{$event}] event was dispatched."
        );
    }

    /**
     * Assert that no events were dispatched.
     */
    public function assertNothingDispatched(): void
    {
        $count = count(Arr::flatten($this->events));

        PHPUnit::assertSame(
            0,
            $count,
            "{$count} unexpected events were dispatched."
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
     */
    public function dispatched(string $event, ?callable $callback = null): Collection
    {
        if (! $this->hasDispatched($event)) {
            return Collection::make();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return Collection::make($this->events[$event])->filter(function ($arguments) use ($callback) {
            return $callback(...$arguments);
        });
    }

    /**
     * Determine if the given event has been dispatched.
     */
    public function hasDispatched(string $event): bool
    {
        return isset($this->events[$event]) && ! empty($this->events[$event]);
    }

    /**
     * Register an event listener with the dispatcher.
     */
    public function listen(array|Closure|string $events, mixed $listener = null): void
    {
        /* @phpstan-ignore-next-line */
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * Determine if a given event has listeners.
     */
    public function hasListeners(string $eventName): bool
    {
        /* @phpstan-ignore-next-line */
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Register an event and payload to be dispatched later.
     */
    public function push(string $event, array $payload = []): void
    {
    }

    /**
     * Register an event subscriber with the dispatcher.
     */
    public function subscribe(object|string $subscriber): void
    {
        /* @phpstan-ignore-next-line */
        $this->dispatcher->subscribe($subscriber);
    }

    /**
     * Flush a set of pushed events.
     */
    public function flush(string $event): void
    {
    }

    /**
     * Fire an event and call the listeners.
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false)
    {
        $name = is_object($event) ? get_class($event) : (string) $event;

        if ($this->shouldFakeEvent($name, $payload)) {
            $this->events[$name][] = func_get_args();

            /* @phpstan-ignore-next-line */
            return;
        }

        /* @phpstan-ignore-next-line */
        return $this->dispatcher->dispatch($event, $payload, $halt);
    }

    /**
     * Determine if an event should be faked or actually dispatched.
     */
    protected function shouldFakeEvent(string $eventName, mixed $payload): bool
    {
        if (empty($this->eventsToFake)) {
            return true;
        }

        return Collection::make($this->eventsToFake)
            ->filter(function ($event) use ($eventName, $payload) {
                return $event instanceof Closure
                            ? $event($eventName, $payload)
                            : $event === $eventName;
            })
            ->isNotEmpty();
    }

    /**
     * Push the event onto the fake events array immediately or after the next database transaction.
     */
    protected function fakeEvent(object|string $event, string $name, array $arguments): void
    {
        $this->events[$name][] = $arguments;
    }

    /**
     * Determine whether an event should be dispatched or not.
     */
    protected function shouldDispatchEvent(string $eventName, mixed $payload): bool
    {
        if (empty($this->eventsToDispatch)) {
            return false;
        }

        return Collection::make($this->eventsToDispatch)
            ->filter(function ($event) use ($eventName, $payload) {
                return $event instanceof Closure
                    ? $event($eventName, $payload)
                    : $event === $eventName;
            })
            ->isNotEmpty();
    }

    /**
     * Remove a set of listeners from the dispatcher.
     */
    public function forget(string $event): void
    {
    }

    /**
     * Forget all of the queued listeners.
     */
    public function forgetPushed(): void
    {
    }

    /**
     * Dispatch an event and call the listeners.
     */
    public function until(object|string $event, mixed $payload = []): mixed
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * Get the events that have been dispatched.
     */
    public function dispatchedEvents(): array
    {
        return $this->events;
    }

    /**
     * Handle dynamic method calls to the dispatcher.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->dispatcher, $method, $parameters);
    }
}
