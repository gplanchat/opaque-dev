<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Bridge\Serializer;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\Keyring;
use Gplanchat\Opaque\Opaque;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * An opaque value leaves ciphered and comes back opaque (OPQ002). Register it before the object
 * and property normalizers.
 *
 * It takes a keyring, not an optional cipher: a serializer that can normalize opaque values is one
 * that ciphers them, there is no configuration where it writes them some other way.
 */
final class OpaqueNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly Keyring $keyring,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @return array{v: int, alg: string, kid?: string, nonce?: string, data: string}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!$data instanceof Opaque) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s.', Opaque::class));
        }

        return $this->keyring->encrypt($data)->toWire();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Opaque;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Opaque
    {
        if (!\is_array($data)) {
            // Never echo the value: it may be a secret someone wrote in clear.
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('An opaque value is expected as a ciphered row, "%s" given.', get_debug_type($data)),
                null,
                ['array'],
                $context['deserialization_path'] ?? null,
            );
        }
        if (!is_a($type, Opaque::class, true)) {
            throw new \InvalidArgumentException(sprintf('%s is not an opaque type.', $type));
        }

        return $this->keyring->decrypt(Ciphered::fromWire($data), $type);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, Opaque::class, true);
    }

    /**
     * @return array<string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        // Subclasses are what applications declare, and they are not listed here: "object", with
        // the answer cached per class, is how a normalizer covers a hierarchy.
        return ['object' => true];
    }
}
