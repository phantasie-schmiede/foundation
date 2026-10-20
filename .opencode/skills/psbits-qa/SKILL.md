---
name: psbits-qa
description: QA and verification protocol for psbits TYPO3 extension repositories: composer validate, PHPStan (analyze:php), php-cs-fixer, yamllint, unit and functional tests, the v13.4/v14.3 test matrix (runTests.sh, runLocalTests.sh, runDockerTests.sh), .Build core switching, coverage baselines. Use when running or planning tests, static analysis, code style checks, or any verification round for a psbits extension.
---

# PSBits QA Protocol

## Mandatory verification block

After any code change, run these steps (skip an individual step only when the repo does not
have it):

1. `composer validate --no-check-publish`
2. `php -l` for every changed PHP file
3. `composer analyze:php` (PHPStan) - **never skip**, even for docblock-only changes
4. `.Build/bin/php-cs-fixer fix --config=Build/Quality/.php-cs-fixer.dist.php --dry-run
   --path-mode=intersection <changed files>`
5. `composer lint:yaml` (or `yamllint`) for changed YAML files
6. `composer test:unit` and `composer test:functional` when the repo defines them

A fully green block is the definition of done for a change.

## Test matrix (repos with `Build/Scripts/`)

`foundation` and `security` carry the full matrix infrastructure:

- `Build/Scripts/runTests.sh <core>` - resolves the core via composer and runs unit +
  functional tests (e.g. `runTests.sh 13.4`, `runTests.sh 14.3`)
- `runLocalTests.sh` - local PHP with docker databases, `runDockerTests.sh` - everything in
  containers, `buildTestImages.sh` - builds the test images
- The matrix environment is written to `composer-matrix.json` (locked in
  `composer-matrix.lock`; both gitignored); `.Build/` holds the resolved core.
- Always verify on **both** supported majors (v13.4 and v14.3) before declaring a change
  done.

## `.Build` core switching

- Switch `.Build` between v13.4 and v14.3 with `composer update 'typo3/*'` inside the repo,
  using the target major constraint.
- After switching cores, wipe stale TF functional instances:
  `.Build/public/typo3temp/var/tests/functional-*` (per-test-class caches hold stale TCA).
- After a core switch, restart DDEV (`ddev restart`) before smoke tests.

## Vendor surgery (published dependency bugs)

`.Build/` resolves *published* versions of sibling psbits repos. If a bug lives in a
published version:

1. Patch `.Build/vendor/<vendor>/<repo>/...` locally to prove the diagnosis and unblock
   verification.
2. Fix the actual source in the sibling repo - the fix only propagates after that repo is
   pushed and re-released or re-tagged.
3. Note which published version is affected (e.g. "foundation 4.0.0 ships the broken TCA
   layout until the 4.0.1 re-release").

## Coverage

- `Build/Scripts/runCoverage.sh` (where present) runs phpunit with coverage and compares
  against `Build/Quality/coverage-baseline.json`.
- The baseline update script (`test:coverage:update-baseline` /
  `runCoverage.sh update-baseline`) rewrites the baseline - only with explicit user approval.

## Environment notes

- DDEV with PHP 8.3 as the workspace interpreter.
- `yamllint` CLI is required for `lint:yaml`.
- In restricted environments, `composer install`/`update` can fail on GitHub dist downloads
  without authentication.
- `composer.lock` is gitignored in these repos; `.Build/` is local only.
