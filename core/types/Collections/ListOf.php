<?php

declare(strict_types=1);

namespace Core\types\Collections;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A C# List<T>-style dynamic array for PHP 8+.
 *
 * Usage:
 *   $ints = new ListOf('int');
 *   $ints->add(1);
 *   $ints->addRange([2, 3]);
 *   $ints[1] = 42;                  // indexer
 *   foreach ($ints as $i) {}        // iterable
 *   $ints->remove(42);
 *   $idx = $ints->indexOf(3);
 *   $count = $ints->count();
 *
 *   $users = new ListOf(App\Model\User::class); // class/interface types
 */
final class ListOf implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int, mixed> */
    private array $items = [];

    /** @var string|null $type Scalar type ('int', 'string', 'float', 'bool', 'array', 'object', 'callable', 'mixed') or FQCN/interface. */
    private ?string $type;

    /** Optional custom validator for complex constraints. Signature: fn(mixed $v): bool */
    private $validator;

    /**
     * @param string|null $type   Scalar type or FQCN/interface name. Null = mixed.
     * @param callable|null $validator Optional: extra predicate to accept/reject values.
     * @param iterable<mixed> $seed Optional initial values.
     */
    public function __construct(?string $type = null, ?callable $validator = null, iterable $seed = [])
    {
        $this->type = $type;
        $this->validator = $validator;

        foreach ($seed as $v) {
            $this->assertType($v);
            $this->items[] = $v;
        }
    }

    // ---------- Core List<T>-like API ----------

    /** Add a single item. */
    public function add(mixed $value): void
    {
        $this->assertType($value);
        $this->items[] = $value;
    }

    /** Add a range of items. */
    public function addRange(iterable $values): void
    {
        foreach ($values as $v) {
            $this->add($v);
        }
    }

    /** Insert at index (like List<T>.Insert). */
    public function insert(int $index, mixed $value): void
    {
        $this->assertBoundsForInsert($index);
        $this->assertType($value);
        array_splice($this->items, $index, 0, [$value]);
    }

    /** Insert many at index. */
    public function insertRange(int $index, iterable $values): void
    {
        $this->assertBoundsForInsert($index);
        $toInsert = [];
        foreach ($values as $v) {
            $this->assertType($v);
            $toInsert[] = $v;
        }
        array_splice($this->items, $index, 0, $toInsert);
    }

    /** Remove first occurrence; returns true if removed. */
    public function remove(mixed $value): bool
    {
        $i = $this->indexOf($value);
        if ($i === -1) {
            return false;
        }
        $this->removeAt($i);
        return true;
    }

    /** Remove at index. */
    public function removeAt(int $index): void
    {
        $this->assertBounds($index);
        array_splice($this->items, $index, 1);
    }

    /** Clear all items. */
    public function clear(): void
    {
        $this->items = [];
    }

    /** Returns index of first match using loose equality by default; -1 if not found. */
    public function indexOf(mixed $value, bool $strict = false): int
    {
        $i = array_search($value, $this->items, $strict);
        return $i === false ? -1 : $i;
    }

    /** Returns index of last match; -1 if not found. */
    public function lastIndexOf(mixed $value, bool $strict = false): int
    {
        for ($i = count($this->items) - 1; $i >= 0; $i--) {
            if ($strict ? $this->items[$i] === $value : $this->items[$i] == $value) {
                return $i;
            }
        }
        return -1;
    }

    /** True if list contains value. */
    public function contains(mixed $value, bool $strict = false): bool
    {
        return $this->indexOf($value, $strict) !== -1;
    }

    /** Number of items (Count property in C#). */
    public function count(): int
    {
        return count($this->items);
    }

    /** Copy to plain array (ToArray in C#). */
    public function toArray(): array
    {
        return $this->items;
    }

    /** Reverse in place. */
    public function reverse(): void
    {
        $this->items = array_reverse($this->items);
    }

    /**
     * Sort in place. If no comparator given and items are scalars, natural sort.
     * @param callable(mixed,mixed):int|null $comparer Return <0, 0, >0 like usort.
     */
    public function sort(?callable $comparer = null): void
    {
        if ($comparer) {
            usort($this->items, $comparer);
            return;
        }
        sort($this->items); // natural ascending for scalars
    }

    /** Find first element matching predicate; null if none. */
    public function find(callable $predicate): mixed
    {
        foreach ($this->items as $v) {
            if ($predicate($v)) {
                return $v;
            }
        }
        return null;
    }

    /** Find all elements matching predicate. */
    public function findAll(callable $predicate): self
    {
        $out = new self($this->type, $this->validator);
        foreach ($this->items as $v) {
            if ($predicate($v)) {
                $out->add($v);
            }
        }
        return $out;
    }

    // ---------- Interfaces for foreach, [], count(), json_encode ----------

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->assertBounds((int)$offset);
        return $this->items[(int)$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assertType($value);
        if ($offset === null) { // $list[] = $value
            $this->items[] = $value;
            return;
        }
        $index = (int)$offset;
        $this->assertBounds($index);
        $this->items[$index] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->removeAt((int)$offset);
    }

public function jsonSerialize(): array
{
    if (empty($this->items)) {
        return [];
    }
    
    return array_values(array_map([$this, 'normalize'], $this->items));
}

/** @return mixed */
private function normalize(mixed $v): mixed
{
    // Respect explicit contracts first
    if ($v instanceof \JsonSerializable) {
        return $v->jsonSerialize();
    }

    // Dates
    if ($v instanceof \DateTimeInterface) {
        return $v->format('c');
    }

    // Nested lists/arrays
    if (is_array($v)) {
        return array_map([$this, 'normalize'], $v);
    }

    if ($v instanceof self) {
        return $v->jsonSerialize();
    }

    // If it’s a plain object without JsonSerializable, try getters:
    if (is_object($v)) {
        $out = [];
        foreach (get_class_methods($v) ?: [] as $m) {
            if (str_starts_with($m, 'get') && (new \ReflectionMethod($v, $m))->getNumberOfRequiredParameters() === 0) {
                $key = lcfirst(substr($m, 3));
                $val = $v->$m();
                $out[$key] = $val instanceof \DateTimeInterface ? $val->format('c') : $val;
            }
        }
        return $out; // falls back to {} if there are no getters
    }

    // Scalars, null, etc.
    return $v;
}


    // ---------- Helpers ----------

    private function assertBounds(int $index): void
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new \OutOfRangeException("Index {$index} is out of range.");
        }
    }

    private function assertBoundsForInsert(int $index): void
    {
        $n = count($this->items);
        if ($index < 0 || $index > $n) {
            throw new \OutOfRangeException("Insert index {$index} is out of range (0..{$n}).");
        }
    }

    private function assertType(mixed $value): void
    {
        if ($this->type === null || $this->type === 'mixed') {
            // optional custom validation only
        } elseif ($this->isScalarType($this->type)) {
            $ok = match ($this->type) {
                'int'      => is_int($value),
                'string'   => is_string($value),
                'float'    => is_float($value),
                'bool'     => is_bool($value),
                'array'    => is_array($value),
                'object'   => is_object($value),
                'callable' => is_callable($value),
                default    => false,
            };
            if (!$ok) {
                $got = get_debug_type($value);
                throw new \TypeError("Expected {$this->type}, got {$got}.");
            }
        } else {
            // FQCN/interface
            if (!($value instanceof $this->type)) {
                $got = get_debug_type($value);
                throw new \TypeError("Expected instance of {$this->type}, got {$got}.");
            }
        }

        if ($this->validator && !($this->validator)($value)) {
            throw new \TypeError("Value rejected by custom validator.");
        }
    }

    private function isScalarType(string $t): bool
    {
        return in_array($t, ['int', 'string', 'float', 'bool', 'array', 'object', 'callable', 'mixed'], true);
    }
}
