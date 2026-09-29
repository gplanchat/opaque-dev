<?php

declare(strict_types=1);

namespace Gplanchat\Opaque;

/**
 * An opaque value once ciphered: what may be stored, journaled or sent (OPQ001).
 *
 * It carries what deciphering needs besides the key — the algorithm, the id of the key, the nonce —
 * because a journal is append-only: a row written today is read after the key has rotated.
 */
final class Ciphered
{
    private const WIRE_VERSION = 1;

    public function __construct(
        public readonly string $algorithm,
        public readonly string $keyId,
        public readonly string $nonce,
        public readonly string $payload,
    ) {}

    /**
     * An empty nonce or key id leaves the wire, so an unkeyed encoding writes only what it has.
     *
     * @return array{v: int, alg: string, kid?: string, nonce?: string, data: string}
     */
    public function toWire(): array
    {
        $wire = ['v' => self::WIRE_VERSION, 'alg' => $this->algorithm];
        if ('' !== $this->keyId) {
            $wire['kid'] = $this->keyId;
        }
        if ('' !== $this->nonce) {
            $wire['nonce'] = base64_encode($this->nonce);
        }
        $wire['data'] = base64_encode($this->payload);

        return $wire;
    }

    /**
     * @param array<mixed> $wire what the journal holds: checked, never trusted
     */
    public static function fromWire(array $wire): self
    {
        if (self::WIRE_VERSION !== ($wire['v'] ?? null)) {
            throw new CipherFailure('Unknown ciphered wire version.');
        }

        return new self(
            self::string($wire, 'alg', required: true),
            self::string($wire, 'kid'),
            self::base64($wire, 'nonce'),
            self::base64($wire, 'data', required: true),
        );
    }

    /**
     * The payload of an unsecured encoding is the clear text: a dump never shows it.
     *
     * @return array{algorithm: string, keyId: string, payload: string}
     */
    public function __debugInfo(): array
    {
        return ['algorithm' => $this->algorithm, 'keyId' => $this->keyId, 'payload' => Opaque::MASK];
    }

    /**
     * @param array<mixed> $wire
     */
    private static function string(array $wire, string $field, bool $required = false): string
    {
        $value = $wire[$field] ?? ($required ? null : '');
        if (!\is_string($value) || ($required && '' === $value && 'data' !== $field)) {
            throw new CipherFailure(sprintf('The ciphered wire field "%s" is missing or malformed.', $field));
        }

        return $value;
    }

    /**
     * @param array<mixed> $wire
     */
    private static function base64(array $wire, string $field, bool $required = false): string
    {
        $decoded = base64_decode(self::string($wire, $field, $required), true);
        if (false === $decoded) {
            throw new CipherFailure(sprintf('The ciphered wire field "%s" is not base64.', $field));
        }

        return $decoded;
    }
}
