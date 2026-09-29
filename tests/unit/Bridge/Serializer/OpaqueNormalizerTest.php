<?php

declare(strict_types=1);

namespace unit\Bridge\Serializer;

use Gplanchat\Opaque\Bridge\Serializer\OpaqueNormalizer;
use Gplanchat\Opaque\Cipher\SodiumSecretBox;
use Gplanchat\Opaque\CipherFailure;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Keyring;
use Gplanchat\Opaque\Opaque;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(OpaqueNormalizer::class)]
final class OpaqueNormalizerTest extends TestCase
{
    private const SECRET = 'Lorem ipsum dolor';

    public function testAnOpaqueLeavesCipheredAndComesBackOpaque(): void
    {
        $serializer = $this->serializer(new Keyring($this->secretBox('k')));

        $json = $serializer->serialize(new Credentials('alice', Password::fromString(self::SECRET)), 'json');
        $credentials = $serializer->deserialize($json, Credentials::class, 'json');

        self::assertStringNotContainsString(self::SECRET, $json);
        self::assertInstanceOf(Credentials::class, $credentials);
        self::assertSame('alice', $credentials->user);
        self::assertInstanceOf(Password::class, $credentials->password);
        self::assertSame(self::SECRET, disclose($credentials->password));
    }

    public function testARowWrittenBeforeARotationStillReads(): void
    {
        $old = $this->secretBox('o');
        $json = $this->serializer(new Keyring($old))->serialize(new Credentials('alice', Password::fromString(self::SECRET)), 'json');

        $credentials = $this->serializer(new Keyring($this->secretBox('n'), $old))->deserialize($json, Credentials::class, 'json');

        self::assertInstanceOf(Credentials::class, $credentials);
        self::assertSame(self::SECRET, disclose($credentials->password));
    }

    public function testARowTheRingCannotReadFailsLoudly(): void
    {
        $json = $this->serializer(new Keyring($this->secretBox('o')))->serialize(new Credentials('alice', Password::fromString(self::SECRET)), 'json');

        $this->expectException(CipherFailure::class);

        $this->serializer(new Keyring($this->secretBox('n')))->deserialize($json, Credentials::class, 'json');
    }

    public function testAClearStringInPlaceOfTheRowIsRefused(): void
    {
        // A secret written in clear into the journal must not be accepted as if it had been ciphered.
        $this->expectException(NotNormalizableValueException::class);

        $this->serializer(new Keyring($this->secretBox('k')))
            ->deserialize('{"user":"alice","password":"' . self::SECRET . '"}', Credentials::class, 'json');
    }

    /**
     * The failure mode OPQ002 exists for: a serializer assembled without the bridge. The stock
     * normalizers must then lose the secret, never write it out.
     *
     * @return iterable<string, array{NormalizerInterface}>
     */
    public static function stockNormalizers(): iterable
    {
        yield 'object normalizer' => [new ObjectNormalizer()];
        yield 'property normalizer' => [new PropertyNormalizer()];
    }

    #[DataProvider('stockNormalizers')]
    public function testWithoutTheBridgeAStockNormalizerDoesNotLeak(NormalizerInterface $normalizer): void
    {
        try {
            $json = (new Serializer([$normalizer], [new JsonEncoder()]))
                ->serialize(new Credentials('alice', Password::fromString(self::SECRET)), 'json');
        } catch (NotNormalizableValueException $refusal) {
            // An object with nothing to read is refused outright: that does not leak either.
            $json = $refusal->getMessage();
        }

        self::assertStringNotContainsString(self::SECRET, $json);
    }

    private function serializer(Keyring $keyring): Serializer
    {
        return new Serializer([new OpaqueNormalizer($keyring), new ObjectNormalizer()], [new JsonEncoder()]);
    }

    private function secretBox(string $filler): SodiumSecretBox
    {
        return new SodiumSecretBox(str_repeat($filler, SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }
}

final class Credentials
{
    public function __construct(
        public readonly string $user,
        public readonly Password $password,
    ) {}
}

final class Password extends Opaque {}
