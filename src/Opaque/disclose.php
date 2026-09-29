<?php

declare(strict_types=1);

namespace Gplanchat\Opaque;

// A path repository installs this package through a symlink, and some tools (PHPStan's workers)
// load the file under both paths, which PHP does not recognise as one. Hence the conditional
// declaration: a top-level one would be hoisted before any guard could run.
if (!\function_exists(__NAMESPACE__ . '\disclose')) {
    /**
     * The one way out of an opaque value (OPQ001). Every call site is a place where a secret becomes
     * a string again: keep them few, and at the edge of the application.
     */
    function disclose(Opaque $opaque): string
    {
        /** @psalm-suppress InaccessibleMethod bound to Opaque's scope, which Psalm does not follow */
        $reveal = \Closure::bind(static fn(Opaque $opaque): string => Opaque::reveal($opaque), null, Opaque::class);

        /** @psalm-suppress PossiblyNullFunctionCall binding a static closure to a class scope cannot fail */
        return $reveal($opaque);
    }
}
