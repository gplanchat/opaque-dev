<?php

declare(strict_types=1);

namespace unit\Opaque\Cipher;

use Gplanchat\Opaque\Cipher\Base64Encoding;
use Gplanchat\Opaque\Cipher\Cipher;
use Gplanchat\Opaque\Cipher\PlainText;
use Gplanchat\Opaque\Cipher\SodiumSealedBox;
use Gplanchat\Opaque\Cipher\SodiumSecretBox;
use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Opaque;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SodiumSecretBox::class)]
#[CoversClass(SodiumSealedBox::class)]
#[CoversClass(Base64Encoding::class)]
#[CoversClass(PlainText::class)]
final class CipherTest extends TestCase
{
    private const SECRET = 'Lorem ipsum dolor';

    /**
     * @return iterable<string, array{Cipher, string}>
     */
    public static function ciphers(): iterable
    {
        yield 'secret box' => [new SodiumSecretBox(str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)), 'sodium-secretbox'];
        yield 'sealed box' => [SodiumSealedBox::fromKeypair(sodium_crypto_box_keypair()), 'sodium-sealedbox'];
        yield 'base64' => [new Base64Encoding(), 'base64'];
        yield 'plain text' => [new PlainText(), 'plain'];
    }

    #[DataProvider('ciphers')]
    public function testWhatItEncryptsItDecrypts(Cipher $cipher, string $algorithm): void
    {
        $ciphered = $cipher->encrypt(Opaque::fromString(self::SECRET));

        self::assertSame($algorithm, $ciphered->algorithm);
        self::assertSame($cipher->keyId(), $ciphered->keyId);
        self::assertSame(self::SECRET, disclose($cipher->decrypt($ciphered)));
    }

    #[DataProvider('ciphers')]
    public function testItRefusesWhatAnotherAlgorithmWrote(Cipher $cipher): void
    {
        $this->expectException(CipherFailure::class);

        $cipher->decrypt(new Ciphered('someone-else', $cipher->keyId(), '', 'payload'));
    }

    public function testTheSecretBoxHidesTheSecret(): void
    {
        $ciphered = $this->secretBox('k')->encrypt(Opaque::fromString(self::SECRET));

        self::assertStringNotContainsString(self::SECRET, serialize($ciphered));
    }

    public function testTheSecretBoxDrawsANewNonceEveryTime(): void
    {
        $cipher = $this->secretBox('k');

        self::assertNotSame(
            $cipher->encrypt(Opaque::fromString(self::SECRET))->nonce,
            $cipher->encrypt(Opaque::fromString(self::SECRET))->nonce,
        );
    }

    public function testTheSecretBoxRefusesAnotherKey(): void
    {
        $ciphered = $this->secretBox('k')->encrypt(Opaque::fromString(self::SECRET));

        $this->expectException(CipherFailure::class);

        $this->secretBox('x')->decrypt(new Ciphered($ciphered->algorithm, $this->secretBox('x')->keyId(), $ciphered->nonce, $ciphered->payload));
    }

    public function testTheSecretBoxRefusesATamperedPayload(): void
    {
        $cipher = $this->secretBox('k');
        $ciphered = $cipher->encrypt(Opaque::fromString(self::SECRET));

        $this->expectException(CipherFailure::class);

        $cipher->decrypt(new Ciphered($ciphered->algorithm, $ciphered->keyId, $ciphered->nonce, $ciphered->payload . 'x'));
    }

    public function testTheSecretBoxRefusesAKeyOfTheWrongLength(): void
    {
        $this->expectException(CipherFailure::class);

        new SodiumSecretBox('too short');
    }

    public function testTwoKeysHaveTwoIds(): void
    {
        self::assertNotSame($this->secretBox('k')->keyId(), $this->secretBox('x')->keyId());
    }

    public function testTheKeyIdDoesNotGiveTheKeyAway(): void
    {
        $key = str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        self::assertStringNotContainsString($key, (new SodiumSecretBox($key))->keyId());
        self::assertStringNotContainsString(bin2hex($key), (new SodiumSecretBox($key))->keyId());
    }

    public function testTheKeyStaysOutOfADump(): void
    {
        $key = str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        self::assertStringNotContainsString($key, print_r(new SodiumSecretBox($key), true));
    }

    public function testASealedBoxWithOnlyThePublicKeyEncryptsButCannotDecrypt(): void
    {
        $keypair = sodium_crypto_box_keypair();
        $writer = SodiumSealedBox::forPublicKey(sodium_crypto_box_publickey($keypair));

        $ciphered = $writer->encrypt(Opaque::fromString(self::SECRET));

        self::assertSame(self::SECRET, disclose(SodiumSealedBox::fromKeypair($keypair)->decrypt($ciphered)));

        $this->expectException(CipherFailure::class);

        $writer->decrypt($ciphered);
    }

    public function testASealedBoxRefusesAnotherKeypair(): void
    {
        $ciphered = SodiumSealedBox::fromKeypair(sodium_crypto_box_keypair())->encrypt(Opaque::fromString(self::SECRET));
        $other = SodiumSealedBox::fromKeypair(sodium_crypto_box_keypair());

        $this->expectException(CipherFailure::class);

        $other->decrypt(new Ciphered($ciphered->algorithm, $other->keyId(), $ciphered->nonce, $ciphered->payload));
    }

    private function secretBox(string $filler): SodiumSecretBox
    {
        return new SodiumSecretBox(str_repeat($filler, SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }
}
