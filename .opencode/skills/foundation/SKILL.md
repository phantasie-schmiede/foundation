---
name: PSBits Foundation
description: psbits/foundation — the base package all psbits extensions register with. 4.2.x branch and release status, the 4.2.2 changes, ExtensionInformation/PageTypeService/PluginService, the v14 IconRegistry fix. Use when working on foundation or its integration points in other extensions.
---

# psbits/foundation

Foundation is the base package that all psbits extensions build on: extension
registration (modules, plugins, page types), TCA wiring at boot, and version
helpers.

## Current branch and release status

- Work happens on branch `4.2.x` (repo: `vendor/psbits/foundation`).
- **4.2.2 is the current release** (tagged on `4.2.x`). Compared to 4.2.1:
  - `[BUGFIX] Document Ctrl labelAlt as a list of property names.` —
    `Ctrl::$labelAlt` is now documented as `list<string>|string|null`, so the
    phpstan `argument.type` errors in consumers passing a list are gone.
  - `[BUGFIX] Skip the IconRegistry check for page type icons on v14.` — see
    the v14 status section below.
  - `[FEATURE] Use the DuplicationBehavior enum for the upload duplication
    behaviour.` — the `uploadDuplicationBehaviour` parameter of the `File`
    TCA attribute now expects the core enum
    `TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior` instead of a string
    (breaking; string values like `'rename'` must be replaced with
    `DuplicationBehavior::RENAME`). Documented in
    `Documentation/Upgrade_v4.md`.
  - `[BUGFIX] Document the File attribute list parameters as string lists.` —
    `File::$allowed` and `File::$uploadFileNameGeneratorProperties` are now
    documented as `list<string>`, so the phpstan `argument.type` errors in
    consumers passing a list are gone.
- All consumer extensions lock `psbits/foundation` at the Packagist tag —
  they pick up 4.2.2 once they run `composer update`.

## Key classes (4.2 structure)

- `PSBits\Foundation\Data\AbstractExtensionInformation` — base for the
  per-extension `Classes/Data/ExtensionInformation.php`; derives extension
  key/name from the class name; builder methods `addModule()`,
  `addMainModule()`, `addPageType()`, `addPlugin()`.
- `PSBits\Foundation\Data\PageTypeConfiguration`, `ModuleConfiguration`,
  `MainModuleConfiguration` — the configuration objects.
- `PSBits\Foundation\Service\Configuration\PageTypeService` —
  `addToSelectBox()` registers page types in the `pages` TCA (`doktype`
  select item + `typeicon_classes`, incl. variant suffixes `-root`,
  `-contentFromPid`, `-hideinmenu` via `ICON_SUFFIXES`); `addToDragArea()`
  (no-op on v14 — the core determines the drag area itself); `addToRegistry()`
  (`PageDoktypeRegistry`).
- `PSBits\Foundation\Service\Configuration\PluginService` — plugin
  registration; the reference implementation of the v14 icon pattern
  (identifier passed through as is, core `IconFactory` falls back).
- `PSBits\Foundation\Utility\Typo3VersionUtility` — `isAtLeast('14.0')`, the
  single place to ask for the running core version.
- `Configuration/TCA/Overrides/pages.php` — runs at boot (TcaFactory) and
  drives `PageTypeService` for every installed extension; this is where the
  v14 IconRegistry issue triggered.

## v14 status

- The IconRegistry-at-boot issue (v14 `ServiceProvider::getIconRegistry()`
  throws while boot is incomplete) is fixed in 4.2.2 (`3d2d3f9`) — see the
  `typo3-v14-migration` skill (in the ddev-typo3 project) for the full
  picture and the safe pattern.
- Only extensions defining page types were affected; within psbits that is
  `templates` only.

## Verification and CI

- The repo carries the shared CI caller `.github/workflows/ci.yml`
  (identical to the queue extensions, managed by `qa link`) — pushes
  trigger the shared workflow matrix in GitHub Actions.
- Local verification with a v14.3.x `.Build`: unit suite green (351 tests on
  v14.3.10); the functional suite needs the `pdo_sqlite` driver, which the
  local PHP build lacks — "could not find driver" errors there are an
  environment issue, not regressions.
- Commit conventions and the pre-commit hook (`composer lint:all`, PATH
  pitfall): see the `psbits-extension-dev` skill.
