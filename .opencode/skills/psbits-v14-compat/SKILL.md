---
name: psbits-v14-compat
description: Reference for TYPO3 v13.4 and v14.3 dual compatibility in psbits extensions: TCA configuration, Extbase models and localization, event listeners, FlexForm setup, the Functional testing framework, PHPStan docblocks, Fluid 5. Use when writing or reviewing code that must run on both TYPO3 major versions, when upgrading to v14, when debugging v13/v14 behavior differences, or when defining TCA or functional tests.
---

# PSBits v13.4/v14.3 Dual Compatibility

Reference for code that must run on both TYPO3 v13.4 and v14.3. Every entry below is verified
against both core versions.

## TCA configuration

- On v14, flat `Configuration/TCA/*.php` files must **return** their TCA fragment. The core
  (`TcaFactory::loadConfigurationTcaFiles()`) only captures the returned array; side effects on
  `$GLOBALS['TCA']` inside flat files are discarded. v13 captured the final `$GLOBALS['TCA']`
  state after the include, so side-effect files worked there.
- Files that build TCA as a side effect (for example attribute-driven runtime builders) must
  live in `Configuration/TCA/Overrides/` instead. The Overrides loader behaves identically in
  v13 and v14 and preserves side effects.
- Filename order within `Overrides/` matters: files are processed alphabetically, so a full TCA
  build must sort before any file that adjusts it.
- Symptom of the flat-file bug on v14: model classes and repositories resolve, but their tables
  never appear in the database (SchemaMigrator does not create them) and `$GLOBALS['TCA']`
  lacks the table keys.

## Functional testing framework (TF) gotchas

- TF functional test instances persist per test class under
  `.Build/public/typo3temp/var/tests/functional-<sha1(test class)[:7]>`, including TCA code
  caches. Wipe that directory (or the whole `typo3temp/var/tests/` tree) before re-running
  after changing TCA file layout or extension structure.
- `testExtensionsToLoad` entries like `typo3conf/ext/<vendor>/<key>` are resolved as exact
  composer package names by TF. The last path segment must equal the package name: use
  single-word extension keys (`tcafixture`), never hyphenated keys, and vendor/package names
  without underscores.
- `ExtensionManagementUtility::convertClassNameToTableName()` does **not** split camelCase:
  `MacGuffin` becomes `macguffin`, giving the table `tx_<ext>_domain_model_macguffin`.
- TCA `ctrl` array keys are snake_case (`default_sortby`), not camelCase.

## Core API differences (v13.4 vs v14)

- The `#[AsEventListener]` attribute exists since v13.0 (not v12). It requires Symfony DI
  `autoconfigure: true`; the listened event is inferred from the `__invoke` method parameter.
  Listener identifiers via `identifier:` are stable across both versions.
- `ExtensionManagementUtility::addPiFlexFormValue()` is deprecated on v14. Write the FlexForm
  definition directly to `types[$cType]['columnsOverrides']['pi_flexform']['config']['ds']` on
  v14 and keep the EMU call on v13 (dual path).
- `BeforeLoadedPageTsConfigEvent` (PSR-14) exists since v13.4 and in v14 - a stable event for
  PageTsConfig manipulation on both majors.
- `PageRepository::defaultPageTSconfig` (and `defaultUserTSconfig`) are removed on v14.
- `ExtensionManagementUtility::subtypes_addlist()` is removed on v14 (keep version-gated calls
  for v13).
- The frontend `site` object is exposed as the request attribute `site` on both majors (set by
  the identical `SiteResolver` middleware); `null` when no site is configured. In FE code this
  is the version-neutral way to obtain the site (fallback: `$GLOBALS['TSFE']` for legacy
  contexts).
- Extbase `Annotation\*` classes are aliased to `Attribute\*` on v14 via the class alias
  loader; model code does not need changes.

## Extbase localization

- `ExtbaseLocalizationUtility::translate()` throws `InvalidArgumentException` when
  `$extensionName` is empty and the key is not a fully qualified `LLL:` key - in v13.4 **and**
  v14. Wrapper methods around it must document this with `@throws InvalidArgumentException`,
  including every further static delegating wrapper, because explicit `@throws` docblocks
  override PHPStan's inference from the method body.

## PHPStan pitfalls

- The psbits PHPStan setups run level 6 with `paths: Classes + Tests/Unit` only; vendor bodies
  are not analyzed. Exceptions thrown by core methods are therefore only visible to the
  analyser through `@throws` docblocks on own wrapper methods.
- An explicit `@throws` list on a method overrides throwables inferred from its body. When a
  wrapper delegates to a method that throws additional exceptions, extend the wrapper's
  `@throws` list instead of relying on inference.

## Fluid 5

- `StrictArgumentProcessor` casts scalar arguments; quoted numeric strings are accepted where
  numbers are expected.
- `AbstractViewHelper::renderStatic()` is removed; undeclared view helper arguments are errors.
