<?php

declare(strict_types=1);

namespace Gplanchat\Opaque;

/**
 * A value that refuses to leave the process by accident (OPQ001).
 *
 * The content is not a property of the instance: it lives in a static map keyed by the instance,
 * so var_dump, print_r, var_export, json_encode, an array cast and every reflection-based
 * normalizer find an object with nothing inside. {@see disclose()} is the one way out.
 *
 * Extend it to name a secret — `final class ApiKey extends Opaque {}` — so a signature says which
 * secret it takes. Everything that guards the content is final.
 */
class Opaque implements \JsonSerializable, \Stringable
{
    public const MASK = '**SECRET**';

    /** @var \WeakMap<Opaque, \SensitiveParameterValue>|null */
    private static ?\WeakMap $contents = null;

    final private function __construct() {}

    final public static function fromString(#[\SensitiveParameter] string $value): static
    {
        $opaque = new static();
        self::contents()[$opaque] = new \SensitiveParameterValue($value);

        return $opaque;
    }

    /**
     * Reached only by {@see disclose()}, which binds itself to this scope: one function, one name
     * for an audit to grep.
     */
    private static function reveal(self $opaque): string
    {
        $content = self::contents()[$opaque] ?? throw new \LogicException('An opaque value has no content.');

        return (string) $content->getValue();
    }

    final public function __toString(): string
    {
        return self::MASK;
    }

    /**
     * @return array{content: string}
     */
    final public function __debugInfo(): array
    {
        return ['content' => self::MASK];
    }

    /**
     * Refused rather than written empty: an empty payload would lose the value silently, and the
     * loss would only surface where it is read back, in another process, later.
     *
     * @return never
     */
    final public function __serialize(): array
    {
        throw LeakRefused::serialize(static::class);
    }

    /**
     * Refused for the same reason as serialize(): `{}` would lose the value silently, and a store
     * that journals payloads with json_encode() would keep that loss for ever.
     */
    final public function jsonSerialize(): never
    {
        throw LeakRefused::serialize(static::class);
    }

    /**
     * @param array<mixed> $data
     */
    final public function __unserialize(array $data): never
    {
        throw LeakRefused::unserialize(static::class);
    }

    /**
     * @param array<mixed> $properties
     */
    final public static function __set_state(array $properties): never
    {
        throw LeakRefused::unserialize(static::class);
    }

    /**
     * A clone would be a new key in the map, and PHP does not tell __clone() which instance it
     * copies: it would come out empty. An immutable value has nothing to gain from a copy anyway.
     */
    private function __clone() {}

    /**
     * @return \WeakMap<Opaque, \SensitiveParameterValue>
     */
    private static function contents(): \WeakMap
    {
        if (null === self::$contents) {
            /** @var \WeakMap<Opaque, \SensitiveParameterValue> $contents */
            $contents = new \WeakMap();
            self::$contents = $contents;
        }

        return self::$contents;
    }
}
