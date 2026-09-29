<?php

declare(strict_types=1);

namespace unit\Opaque;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ciphered values cross the journal, which is append-only: a row written today is read by a newer
 * deployment weeks later. Both halves of that are tested — the round trip, and the shape a row was
 * written with.
 */
#[CoversClass(Ciphered::class)]
#[CoversClass(CipherFailure::class)]
final class CipheredTest extends TestCase
{
    public function testItSurvivesTheJournalBothWays(): void
    {
        $ciphered = new Ciphered('sodium-secretbox', 'k1', "\x00nonce\xff", "\x01payload\xfe");

        self::assertEquals($ciphered, Ciphered::fromWire($ciphered->toWire()));
    }

    public function testTheWireShapeIsTheOneVersionOneWrote(): void
    {
        $wire = (new Ciphered('sodium-secretbox', 'k1', 'nonce', 'payload'))->toWire();

        self::assertSame([
            'v' => 1,
            'alg' => 'sodium-secretbox',
            'kid' => 'k1',
            'nonce' => base64_encode('nonce'),
            'data' => base64_encode('payload'),
        ], $wire);
    }

    public function testAnEmptyNonceAndKeyLeaveTheWire(): void
    {
        self::assertSame(
            ['v' => 1, 'alg' => 'plain', 'data' => base64_encode('payload')],
            (new Ciphered('plain', '', '', 'payload'))->toWire(),
        );
        self::assertEquals(
            new Ciphered('plain', '', '', 'payload'),
            Ciphered::fromWire(['v' => 1, 'alg' => 'plain', 'data' => base64_encode('payload')]),
        );
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function malformedWire(): iterable
    {
        yield 'unknown version' => [['v' => 2, 'alg' => 'plain', 'data' => '']];
        yield 'no version' => [['alg' => 'plain', 'data' => '']];
        yield 'no algorithm' => [['v' => 1, 'data' => '']];
        yield 'empty algorithm' => [['v' => 1, 'alg' => '', 'data' => '']];
        yield 'no data' => [['v' => 1, 'alg' => 'plain']];
        yield 'data is not base64' => [['v' => 1, 'alg' => 'plain', 'data' => '*not base64*']];
        yield 'nonce is not base64' => [['v' => 1, 'alg' => 'plain', 'nonce' => '*', 'data' => '']];
        yield 'key id is not a string' => [['v' => 1, 'alg' => 'plain', 'kid' => 12, 'data' => '']];
    }

    /**
     * @param array<mixed> $wire
     */
    #[DataProvider('malformedWire')]
    public function testAMalformedRowIsRefused(array $wire): void
    {
        $this->expectException(CipherFailure::class);

        Ciphered::fromWire($wire);
    }

    public function testADumpDoesNotShowThePayload(): void
    {
        // An unsecured encoding carries the clear text as its payload: a dump must not show it.
        $dump = print_r(new Ciphered('plain', '', '', 'Lorem ipsum dolor'), true);

        self::assertStringNotContainsString('Lorem ipsum dolor', $dump);
    }
}
