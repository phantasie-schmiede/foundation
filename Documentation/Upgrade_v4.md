PSBits Foundation 4.2 Upgrade Guide
===================================

Breaking changes!
-----------------

TYPO3 v14 registers plugins as their own content types instead of `list_type` subtypes of the list content type.
Foundation 4.2 adapts the plugin registration to that change and works on both supported core majors (v13 and v14).

- The content element wizard registration is now implemented via the core event
  `ModifyNewContentElementWizardItemsEvent` instead of page TSconfig (which was removed in v14).
  Excluding a plugin from the wizard via the `addToElementWizard` property of `PluginConfiguration` now works on both
  majors; on v14 the exclusion had no effect before.
- `PluginService::addPluginToElementWizard()` was removed.
    - If your extension code calls this method, remove the call. The wizard registration happens automatically for
      every plugin declared in your `ExtensionInformation` class.
- FlexForms of plugins are now registered on the plugin's content type on v14, as the core does it.

Data migration
--------------

Content elements created with `CType = list` and one of the plugin `list_type` values before the upgrade to TYPO3 v14
must be converted to the new plugin content type, otherwise they are no longer rendered in the frontend.

The core upgrade wizards do not cover these records, because they only know the `list_type` values of the core itself.
Foundation therefore provides the upgrade wizard `foundation_listTypeToCType`, which converts the affected content
elements of all installed extensions that provide an `ExtensionInformation` class (including the corresponding backend
user group permissions).

- Run the upgrade via the install tool or `typo3 upgrade:wizards:run` as usual.
- The wizard only becomes active on TYPO3 v14 when there are plugin records to convert.

No further action is required.
