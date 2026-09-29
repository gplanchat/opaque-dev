<?php

declare(strict_types=1);

namespace unit\Opaque;

use Gplanchat\Opaque\Cipher\PlainText;
use Gplanchat\Opaque\Cipher\SodiumSecretBox;
use Gplanchat\Opaque\CipherFailure;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Keyring;
use Gplanchat\Opaque\Opaque;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Keyring::class)]
final class KeyringTest extends TestCase
{
    public function testItEncryptsWithTheCurrentKey(): void
    {
        $current = $this->secretBox('n');
        $keyring = new Keyring($current, $this->secretBox('o'));

        self::assertSame($current->keyId(), $keyring->encrypt(Opaque::fromString('secret'))->keyId);
    }

    public function testARowWrittenBeforeARotationStillReads(): void
    {
        $old = $this->secretBox('o');
        $row = $old->encrypt(Opaque::fromString('secret'));

        $rotated = new Keyring($this->secretBox('n'), $old);

        self::assertSame('secret', disclose($rotated->decrypt($row)));
    }

    public function testARowFromAKeyItDoesNotHoldIsRefused(): void
    {
        $row = $this->secretBox('o')->encrypt(Opaque::fromString('secret'));

        $this->expectException(CipherFailure::class);

        (new Keyring($this->secretBox('n')))->decrypt($row);
    }

    public function testAnUnsecuredEncodingIsReadOnlyWhenItIsOnTheRing(): void
    {
        // A secure ring must not accept a clear-text row someone slipped into the journal.
        $row = (new PlainText())->encrypt(Opaque::fromString('secret'));

        $this->expectException(CipherFailure::class);

        (new Keyring($this->secretBox('n')))->decrypt($row);
    }

    public function testItRestoresTheTypeItIsAskedFor(): void
    {
        $keyring = new Keyring($this->secretBox('n'));

        $password = $keyring->decrypt($keyring->encrypt(Password::fromString('secret')), Password::class);

        self::assertInstanceOf(Password::class, $password);
        self::assertSame('secret', disclose($password));
    }

    private function secretBox(string $filler): SodiumSecretBox
    {
        return new SodiumSecretBox(str_repeat($filler, SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }
}

final class Password extends Opaque {}
