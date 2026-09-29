<?php

declare(strict_types=1);

namespace Gplanchat\Opaque;

/**
 * An opaque value was asked to leave, or to come back, through a channel that would expose it.
 */
final class LeakRefused extends \LogicException
{
    public static function serialize(string $class): self
    {
        return new self(sprintf('%s cannot be serialized: cipher it (OPQ001) or disclose it explicitly.', $class));
    }

    public static function unserialize(string $class): self
    {
        return new self(sprintf('%s cannot be rebuilt from a serialized or exported form.', $class));
    }

}
