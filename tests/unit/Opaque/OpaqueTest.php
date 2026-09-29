<?php

declare(strict_types=1);

namespace unit\Opaque;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\LeakRefused;
use Gplanchat\Opaque\Opaque;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

/**
 * Every channel OPQ001 closes, one test each: the secret must never come out of it.
 */
#[CoversClass(Opaque::class)]
#[CoversClass(LeakRefused::class)]
#[CoversFunction('Gplanchat\Opaque\disclose')]
final class OpaqueTest extends TestCase
{
    private const SECRET = 'Lorem ipsum dolor';

    public function testDiscloseIsTheWayOut(): void
    {
        self::assertSame(self::SECRET, disclose(Opaque::fromString(self::SECRET)));
    }

    public function testAStringCastShowsTheMask(): void
    {
        self::assertSame('**SECRET**', (string) Opaque::fromString(self::SECRET));
    }

    public function testVarDumpDoesNotLeak(): void
    {
        ob_start();
        /** @psalm-suppress ForbiddenCode the dump is what this test reads */
        var_dump(Opaque::fromString(self::SECRET));
        $dump = (string) ob_get_clean();

        self::assertStringNotContainsString(self::SECRET, $dump);
        self::assertStringContainsString('**SECRET**', $dump);
    }

    public function testPrintRDoesNotLeak(): void
    {
        self::assertStringNotContainsString(self::SECRET, print_r(Opaque::fromString(self::SECRET), true));
    }

    public function testVarExportDoesNotLeak(): void
    {
        self::assertStringNotContainsString(self::SECRET, var_export(Opaque::fromString(self::SECRET), true));
    }

    public function testJsonEncodeRefusesRatherThanLoseTheValue(): void
    {
        $this->expectException(LeakRefused::class);

        json_encode(Opaque::fromString(self::SECRET));
    }

    public function testReflectionOnTheInstanceFindsNothing(): void
    {
        $opaque = Opaque::fromString(self::SECRET);

        $instanceProperties = array_filter(
            (new \ReflectionObject($opaque))->getProperties(),
            static fn(\ReflectionProperty $property): bool => !$property->isStatic(),
        );

        self::assertSame([], $instanceProperties);
        self::assertSame([], get_object_vars($opaque));
        self::assertSame([], (array) $opaque);
    }

    public function testSerializeRefusesRatherThanLoseTheValue(): void
    {
        $this->expectException(LeakRefused::class);

        serialize(Opaque::fromString(self::SECRET));
    }

    public function testUnserializeRefusesAForgedPayload(): void
    {
        $this->expectException(LeakRefused::class);

        unserialize('O:23:"Gplanchat\Opaque\Opaque":1:{s:7:"content";s:17:"Lorem ipsum dolor";}');
    }

    public function testSetStateRefusesToRebuildFromAnExport(): void
    {
        $this->expectException(LeakRefused::class);

        Opaque::__set_state(['content' => self::SECRET]);
    }

    public function testTheSecretStaysOutOfAStackTrace(): void
    {
        try {
            (static function (Opaque $opaque): never {
                throw new \RuntimeException('boom');
            })(Opaque::fromString(self::SECRET));
        } catch (\RuntimeException $exception) {
            self::assertStringNotContainsString(self::SECRET, $exception->getTraceAsString());
            self::assertStringNotContainsString(self::SECRET, print_r($exception->getTrace(), true));
        }
    }

    public function testACloneIsRefusedRatherThanEmpty(): void
    {
        $this->expectException(\Error::class);

        /** @psalm-suppress InvalidClone the refusal is what this test checks */
        clone Opaque::fromString(self::SECRET);
    }

    public function testASubclassIsBuiltAsItself(): void
    {
        $key = ApiKey::fromString(self::SECRET);

        self::assertInstanceOf(ApiKey::class, $key);
        self::assertSame(self::SECRET, disclose($key));
    }

    public function testTwoOpaquesDoNotShareTheirContent(): void
    {
        $first = Opaque::fromString('first');
        $second = Opaque::fromString('second');

        self::assertSame('first', disclose($first));
        self::assertSame('second', disclose($second));
    }
}

/**
 * A typed secret: what an application declares so a signature says which secret it takes.
 */
final class ApiKey extends Opaque {}
