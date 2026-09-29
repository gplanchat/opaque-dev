#!/usr/bin/env bash
# Every package declares what it imports. Two checks:
#   1. `composer validate --strict` on the root manifest and on every manifest under src/.
#   2. composer-require-checker on every published package (the SPLITS list of
#      bin/splitsh-publish.sh), against the root vendor/. A symbol a package uses on purpose
#      without requiring it (an optional integration, a PHPUnit helper) sits in that package's
#      composer-require-checker.json, next to its `suggest` entry.
# COMPOSER_REQUIRE_CHECKER overrides the checker command; it needs PHP 8.4 or later.
set -euo pipefail
cd "$(dirname "$0")/.."

CHECKER=${COMPOSER_REQUIRE_CHECKER:-composer-require-checker}
status=0

# The root requires its path packages at @dev on purpose: they are this repository.
composer validate --strict --no-check-all --no-check-publish composer.json || status=1
for manifest in src/*/composer.json src/Bridge/*/composer.json; do
    composer validate --strict "$manifest" || status=1
done

for dir in $(sed -n 's#^ *"\(src/[^|]*\)/|.*#\1#p' bin/splitsh-publish.sh); do
    echo "── composer-require-checker $dir"
    config=()
    [ -f "$dir/composer-require-checker.json" ] && config=(--config-file="$PWD/$dir/composer-require-checker.json")
    ln -sfn "$PWD/vendor" "$dir/vendor"
    (cd "$dir" && "$CHECKER" check --no-interaction "${config[@]}" composer.json) || status=1
    rm "$dir/vendor"
done

exit $status
