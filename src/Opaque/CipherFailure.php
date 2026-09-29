<?php

declare(strict_types=1);

namespace Gplanchat\Opaque;

/**
 * A value could not be ciphered or deciphered. Never carries the clear text, nor the key.
 */
final class CipherFailure extends \RuntimeException {}
