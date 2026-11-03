name: General Agent
description: A general-purpose agent for handling a wide variety of tasks with a focus on clarity, simplicity, and precision.

## 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:

- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

## 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

## 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:

- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it - don't delete it.

When your changes create orphans:

- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

## 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:

- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:

```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

## Repository purpose

TYPO3 v13.4/v14.3 extension (`type: typo3-cms-extension`): the PSBits Foundation - configuration
framework for TYPO3 extension development. It generates the TCA and the database schema from PHP
attributes on domain models, centralizes the registration of plugins, modules and page types, and
auto-registers FlexForms, TypoScript, TSconfig and icons. PHP namespace: `PSBits\Foundation\`.
`README.md` is the complete, worked documentation - reference it, do not duplicate it.
`Documentation/Upgrade_v2.md`…`Upgrade_v4.md` cover upgrades from earlier majors.

## Key locations

- `Classes/Attribute/TCA/` — `Ctrl`, `Column`, `Palette`, `Tab`, `Type`, `Mapping` and
  `ColumnType/*` - the attributes that drive TCA and schema generation.
- `Classes/Attribute/PluginAction.php`, `Classes/Attribute/ModuleAction.php` — action
  registration attributes.
- `Classes/Data/` — `AbstractExtensionInformation` (the base class other extensions'
  `ExtensionInformation.php` extends) plus `PluginConfiguration`, `ModuleConfiguration`,
  `MainModuleConfiguration`, `PageTypeConfiguration`.
- `Classes/Service/Configuration/` — `TcaService`, `PluginService`, `ModuleService`,
  `PageTypeService`, `FlexFormService`, `CacheConfigurationBuilder/`, `PageCacheService`.
- `Classes/EventListener/` — boot listeners (e.g. content element wizard plugin registration).
- `Classes/Controller/`, `Classes/ViewHelpers/` (the `foundation` Fluid namespace),
  `Classes/Utility/`.
- `Configuration/TCA/Overrides/` — `buildTcaForAllDomainModels.php` /
  `buildTcaOverridesOfDomainModels.php` (the generation entrypoints, side-effect files on purpose)
  plus overrides for core tables (`pages.php`, `tt_content.php`, `sys_template.php`).
- `Configuration/` — `Services.yaml`, `Backend/Modules.php`, `Sets/Foundation/`, `TypoScript/`,
  `User.tsconfig`, `Extbase/Persistence/Classes.php`, `ExpressionLanguage.php`, `Icons.php`.
- `ext_tables.sql` — the own tables `tx_foundation_accessed_language_labels` and
  `tx_foundation_missing_language_labels` (language label logging).
- `Tests/Unit/`, `Tests/Functional/`.

## How this extension works (read before changing)

- TCA generation runs at boot: the `TCA/Overrides/buildTca*.php` side-effect files scan all active
  packages for `Classes/Data/ExtensionInformation.php` (classes implementing
  `ExtensionInformationInterface`), then scan each package's `Classes/Domain/Model/` for
  non-abstract models carrying a `#[Ctrl]` attribute and build TCA plus database definitions
  from the `#[Column]`/`ColumnType` attributes.
- Other extensions therefore ship **no TCA files and usually no `ext_tables.sql`** - both are
  derived here. If a generated table or TCA entry is missing at runtime, the problem is in the
  attributes, the boot process, or the package state - not in missing files.
- The "log missing language labels" extension setting stores gaps in
  `tx_foundation_missing_language_labels` (fixed entries are removed on the next cache flush).

## Foundation integration (self)

Full conventions for dependent extensions: `psbits-foundation` skill
(`.opencode/skills/psbits-foundation/SKILL.md`); the worked examples are `README.md`.

- This repo's own TCA for core tables lives in `Configuration/TCA/Overrides/` (side-effect files -
  the only place manual TCA code belongs, on both core majors).
- Backend modules use `Configuration/Backend/Modules.php` (thin wrapper) + labels in
  `Resources/Private/Language/Backend/Modules/`.

## Architecture and conventions

- Strongly typed PHP (`declare(strict_types=1);`) is standard for classes.
- Service wiring uses Symfony DI autowiring in `Configuration/Services.yaml`.
- Use yoda conditions for better readability and to avoid accidental assignment.
- Use alphabetical ordering where possible (e.g., in arrays, attributes, imports, properties,
  variable declarations) for consistency.
- Include punctuation in language files for complete localization, e.g. colons after labels should
  not be placed in the template, but in the language file itself.

## Safe change workflow

1. Identify the layer to change first.
2. Keep edits surgical and consistent with existing code style.

If you have questions about architecture, conventions, or best practices, ask before implementing.
If you notice inconsistencies between the codebase and these instructions, mention them - don't fix
them unless asked.

## Validation

Repository quality checks are defined in `composer.json` and the shared `CI` GitHub Actions
workflow (`.github/workflows/ci.yml`). Quality configuration files are centralized in
`Build/Quality/`.

- `composer lint:all` - read-only verification (all linters).
- `composer fix:all` - auto-fix (rewrites source files).
- `composer test:unit` / `composer test:functional` - run the respective suite.
- A pre-commit hook (installed by `.Build/bin/qa link` from the psbits/qa package) runs
  `composer lint:all`.

## Onboarding run notes (errors/workarounds)

- Running QA locally requires installed dependencies (`composer install` / `composer update`, plus
  npm dependencies via Composer post-install).
- In restricted environments, Composer dependency installation may fail if GitHub authentication
  for dist downloads is unavailable.
