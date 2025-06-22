# PSBits Foundation

## Enhanced extension programming in Extbase (supports TYPO3 v12 and v13)

---
**IMPORTANT:**
The vendor and extension name have changed from `psb/psb-foundation` to `psbits/foundation`!
Have a look into the `Documentation/` directory for information about upgrading from a previous version.
---

- [What does it do?](#what-does-it-do)
- [Why should you use it?](#why-should-you-use-it)
- [Getting started](#getting-started)
- [TCA generation](#tca-generation)
    - [Tabs and palettes](#tabs-and-palettes)
    - [Database definitions](#database-definitions)
    - [Extending domain models](#extending-domain-models)
    - [Default language label paths](#default-language-label-paths)
- [Registering and configuring plugins](#registering-and-configuring-plugins)
    - [FlexForms](#flexforms)
    - [Content element wizard](#content-element-wizard)
    - [Custom page types for single plugins](#custom-page-types-for-single-plugins)
- [Registering and configuring modules](#registering-and-configuring-modules)
- [Registering custom page types](#registering-custom-page-types)
- [Auto-registration of TypoScript-files](#auto-registration-of-typoscript-files)
- [Auto-registration of TSconfig-files](#auto-registration-of-tsconfig-files)
- [Auto-registration of icons](#auto-registration-of-icons)
- [Extension settings](#extension-settings)
- [Helper classes](#helper-classes)
    - [ContextUtility](#contextutility)
    - [GlobalVariableService](#globalvariableservice)
    - [StringUtility](#stringutility)
    - [TranslateViewHelper](#translateviewhelper)
    - [TypoScriptProviderService](#typoscriptproviderservice)
    - [UploadService](#uploadservice)

### What does it do?

This extension

- generates the TCA for your domain models by reading their php attributes
- offers autocompletion for TCA by constructor properties of attribute classes
- generates database definitions for your domain models
- simplifies the registration of modules, plugins and page types
- automatically registers existing FlexForms, TypoScript and icons
- provides an easy-to-use frontend file upload with automatic reference creation to given domain models
- provides an optimized TranslateViewHelper with additional options (e.g. plural forms and better variable syntax)
- allows the definition of frontend variables which can be used all over the site (e.g. IBAN or telephone numbers)
- provides convenient ways to access often used core-functionalities

#### IMPORTANT

foundation is designed to simplify common recurrent settings. Although it offers a versatile set of configurations
and tools, you may not be able to map certain complex scenarios with it.
But you can still make use of this extension.
For example, you can use the auto-generation of the TCA and add special settings via files in `TCA/Overrides/` as usual.

### Why should you use it?

The goal of this extension is to

- bring together different points of configuration to improve readability, maintainability and development time
- allow code completion for TCA-settings
- reduce duplicated code
- reduce number of hard-coded string identifiers and keys, and therefore the likelihood of errors due to typos

### Getting started

Create the following file: `EXT:your_extension/Classes/Data/ExtensionInformation.php`. Define the class and make
it extend `PSBits\Foundation\Data\AbstractExtensionInformation`.

Example:

```php
<?php
declare(strict_types=1);

/** copyright... */

namespace Vendor\ExtensionName\Data;

use PSBits\Foundation\Data\AbstractExtensionInformation;

class ExtensionInformation extends AbstractExtensionInformation
{
}
```

You can use inherited functions like `getExtensionKey()` or `getVendorName()` to get rid of hard-coded identifiers, if
you want to.

foundation (don't forget to add it to your extension's dependencies) searches all active packages for the
file `EXT:your_extension/Classes/Data/ExtensionInformation.php` and checks if that class implements
the `PSBits\Foundation\Data\ExtensionInformationInterface`. All extensions that meet these requirements are taken into
account during automated configuration processes, e.g. during TCA generation or icon registration.

### TCA generation

You don't need to create a special file for your domain model in `Configuration/TCA` anymore!
foundation will scan your `Classes/Domain/Model`-directory for all classes (skipping abstract ones) that have an
attribute of type `PSBits\Foundation\Attribute\TCA\Ctrl` in their PHPDoc-comment. The script checks if your model
relates to an existing table in the database and detects if it extends another model from a different extension and
manipulates the TCA accordingly.

You can provide configuration options via attributes.
The attribute `PSBits\Foundation\Attribute\TCA\Column` provides general configuration fields for all TCA types, e. g.
required, displayCond and nullable.
Additional attributes with default values for specific types can be found in
`foundation/Classes/Attribute/TCA/ColumnType/`.

Simple example:

```php
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\Ctrl;

#[Ctrl(label: 'name', searchFields: ['description', 'name')]
class YourClass
{
    #[Column(required: true)]
    #[Input(eval: 'trim')]
    protected string $name = '';

    // You can leave out the brackets if you are fine with the default values.
    #[Input]
    protected string $inputUsingTheDefaultValues = '';

    #[Column(exclude: true)]
    #[Check]
    protected bool $adminOnly = true;

    ...
}
```

Properties without TCA\[...]-attribute will not be considered in TCA-generation.
Some configurations will be added automatically if specific fields are defined in the CTRL attribute
(which they are by default):

| Property                  | Default value    |
|---------------------------|------------------|
| enableColumns > disabled  | hidden           |
| enableColumns > endtime   | endtime          |
| enableColumns > starttime | starttime        |
| languageField             | sys_language_uid |
| transOrigDiffSourceField  | l10n_diffsource  |
| transOrigPointerField     | l10n_parent      |
| translationSource         | l10n_source      |

The fields for enableColumns will be added in additional tabs at the end of `showitems` for all types.

The relational types `inline` and `select` have a special property named `linkedModel`.
Instead of using `foreign_table` you can specify the class name of the related domain model and foundation will
insert the corresponding table name into the TCA.

Additionally, the properties `foreign_field` and `mm_opposite_field` accept property names.
These will be converted to column names.

Extended example:

```php
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\Ctrl;

#[Ctrl(
    hideTable: true,
    label: 'richText',
    labelAlt: [
        'someProperty',
        'anotherProperty',
    ],
    labelAltForce: true,
    sortBy: 'customSortField',
    type: 'myType',
)]
class YourModel
{
    /*
     * This constant is used as value for TCA-property "items". The keys will be used as label identifiers - converted
     * to lowerCamelCase:
     * EXT:your_extension/Resources/Private/Language/Backend/Configuration/TCA/(Overrides/)[modelName].xlf:myType.default
     * EXT:your_extension/Resources/Private/Language/Backend/Configuration/TCA/(Overrides/)[modelName].xlf:myType.recordWithImage
     */
    public const TYPES = [
        'DEFAULT'           => 'default',
        'RECORD_WITH_IMAGE' => 'recordWithImage',
    ];

    #[Select(items: self::TYPES)]
    protected string $myType = self::TYPES['DEFAULT'];

    #[Text(
        cols: 40,
        enableRichtext: true,
        rows: 10,
    )]
    protected string $richText = '';

    /**
     * @var ObjectStorage<AnotherModel>
     */
    #[Inline(
        linkedModel: AnotherModel::class,
        foreignField: 'yourModel',
    )]
    protected ObjectStorage $inlineRelation;

    #[Column(position: 'before:propertyName2')]
    #[Select(linkedModel: AnotherModel::class)]
    protected ?AnotherModel $simpleSelectField = null;

    // This field is only added to the types 'interview' and 'profile'.
    #[Column(typeList: 'interview, profile')]
    #[File(
        allowed: [
            'jpeg',
            'jpg',
        ],
        maxItems: 1,
    )]
    protected ?FileReference $image = null;

    ...
}
```

#### Tabs and palettes

The argument position allows you to assign a field to a specific tab or palette.

Example:

```php
#[Column(position: 'palette:my_palette')]
protected string $description = '';

#[Column(position: 'tab:my_tab')]
protected string $note = '';
```

Without further configuration, tab and palette will be registered with the given identifier and added to the
end of the showitems-list. But you can add additional information for tabs and palettes.
For now, the identifiers of palettes and tabs have to be written in snake_case!

```php
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;

/**
 * @TCA\Tab(identifier="my_tab", label="Custom label", position="after:someProperty")
 */
#[Ctrl]
#[Palette(
    description: 'LLL:EXT:[...]',
    identifier: 'my_palette',
    position: 'before:someProperty'
)]
#[Tab(
    identifier: 'my_tab',
    label: 'Custom label',
    position: 'after:someProperty'
)]
class YourModel
{
    #[Column(position: 'palette:my_palette')]
    protected string $description = '';

    #[Column(position: 'tab:my_tab')]
    protected string $note = '';

    ...
}
```

It's possible to use the `tab:`-prefix for a palette's position, too.<br>
If a property references a field inside a palette there are two more special prefixes that can be used:

- newLineAfter
- newLineBefore

These will insert an additional `--linebreak--` between those fields.

The label of tabs and palettes is determined in the following way:

1. Use the attribute if given. (can be a LLL-reference)
2. Use the default language label if existent.
   (
   see [Default language label paths and additional configuration options](#default-language-label-paths-and-additional-configuration-options))
3. Use the identifier.

The behaviour for the description of palettes is similar:

1. Use the attribute if given. (can be a LLL-reference)
2. Use the default language label if existent.
   (
   see [Default language label paths and additional configuration options](#default-language-label-paths-and-additional-configuration-options))
3. Leave empty.

#### Database definitions

Database definitions are added automatically based on the TCA-attributes used for your properties.
You can override the auto-generated definition by defining the field in the `ext_tables.sql` by yourself or by using the
property `databaseDefinition` of attribute `PSBits\Foundation\Attribute\TCA\Column`.

Priority order:

1. ext_tables.sql
2. #[Column(databaseDefinition: '...')]
3. default of ColumnType attribute

#### Extending domain models

When you are extending domain models (even from extensions that don't make use of foundation) you have to add the
@TCA\Ctrl-attribute! You have the possibility to override ctrl-settings. If you don't want to override anything: just
leave out the brackets. The default values of the attribute class will have no effect in this case.

#### Default language label paths

These language labels will be tried if you don't provide a custom value for them.
The path always is
`EXT:your_extension/Resources/Private/Language/Backend/Configuration/TCA/(Overrides/)[modelName].xlf`.

| Configuration value   | Default language label           |
|-----------------------|----------------------------------|
| ctrl->title           | ctrl.title                       |
| property->label       | [propertyName]                   |
| property->description | [propertyName].description       |
| palette->description  | palette.[identifier].description |
| palette->label        | palette.[identifier].label       |
| tab->label            | tab.[identifier].label           |

When you use the items-property for a select field, you may provide a simple associative array.
It will be transformed into the required multi-level format.
The labels will be build this way:<br>
`[propertyName].[arrayKeyTransformedToLowerCamelCase]`

### Registering and configuring plugins

- Classes/Data/ExtensionInformation.php
  ```php
  public function __construct()
    {
        parent::__construct();
        $this->addPlugin(GeneralUtility::makeInstance(PluginConfiguration::class,
            controllers: [MyController::class],
            name: 'MyPlugin',
        ));
        $this->addPlugin(GeneralUtility::makeInstance(PluginConfiguration::class,
            controllers: [
                MyController::class => [
                    'specificAction',
                ],
                AnotherController::class => [
                    'specificAction', // This is not necessarily the default action!
                    'anotherSpecificAction',
                ],
            ]],
            group: 'customTabInContentElementWizard'
            name: 'AnotherPlugin',
        ));
    }
  ```

- Classes/Controller/YourController.php
  ```php
  use PSBits\Foundation\Attribute\PluginAction;

  class YourController extends ActionController
  {
      #[PluginAction]
      public function simpleAction(): ResponseInterface
      {
          ...
      }

      #[PluginAction(default: true)]
      public function mainAction(): ResponseInterface
      {
          ...
      }

      #[PluginAction(uncached: true)]
      public function uncachedAction(): ResponseInterface
      {
          ...
      }
  }
  ```

Actions without the `PluginAction`-attribute won't be registered -
even if mentioned in the optional action list in `ExtensionInformation.php`!<br>
If no action list is provided, all actions with the `PluginAction`-attribute will be registered.
Which action will be used as default action and which actions should not be cached is determined by the attributes
property values only.
Check the default values and comments in `EXT:foundation/Classes/Attribute/`.

#### FlexForms

If there is a file named `EXT:your_extension/Configuration/FlexForms/[PluginName].xml` it will be registered
automatically. You can override this default by passing a value for the `flexForm`-property to the
`PluginConfiguration`-constructor. You can either provide a filename if your XML-file is located inside the
`Configuration/FlexForms/`-directory or a full file path beginning with `EXT:`.

#### Content element wizard

Plugins will be added to the wizard automatically. There will be a tab for each vendor. You can override the location of
your wizard entry by setting the `group`-property of `PluginConfiguration`. The following language labels are
taken into account automatically if defined:

- `EXT:your_extension/Resources/Private/Language/Backend/Configuration/TsConfig/Page/Mod/Wizards/newContentElement.xlf:`
    - `[group].elements.[pluginName].description`
    - `[group].elements.[pluginName].title`

If the label for title does not exist, this label will be tried:
`EXT:your_extension/Resources/Private/Language/Backend/Configuration/TCA/Overrides/tt_content.xlf:plugin.[pluginName].title`<br>
This label is also used for the select box item (CType)`.
If it doesn't exist either, the plugin name will be used as fallback.

`[group]` defaults to the vendor name (lowercase) if not set within `PluginConfiguration`. That also defines the tab
of the content element wizard. If a new tab is created, its label will be fetched from here:
`EXT:your_extension/Resources/Private/Language/Backend/Configuration/TsConfig/Page/Mod/Wizards/newContentElement.xlf:[group].header`

#### Custom page types for single plugins

You can add a custom page type that renders a specific plugin only.
Simply define a typeNum in your PluginConfiguration:

Classes/Data/ExtensionInformation.php

```php
public function __construct()
{
    parent::__construct();
    $this->addPlugin(GeneralUtility::makeInstance(PluginConfiguration::class,
        controllers: [
            MyController::class
        ],
        name: 'MyPlugin',
        typeNum: 1589385441,
    ));
}
```

foundation will create the necessary TypoScript so that this plugin can be called directly with the request parameter
`type=1589385441`.
If you defined a typeNum, you can add more specific information for that page type:

| Option                      | Type               | Description                                                                | Default   |
|-----------------------------|--------------------|----------------------------------------------------------------------------|-----------|
| typeNumCacheable            | boolean            | whether the output should be cached?                                       | false     |
| typeNumContentType          | ContentType (Enum) | defines the information about the content sent in header                   | text/html |
| typeNumDisableAllHeaderCode | boolean            | whether the plugin output should not be wrapped inside the page's template | true      |

### Registering and configuring modules

This process is very similar to the way plugins are registered.
You only have to add a special registration file for modules, which is required by the core.
The content of this file can always be almost identical (see example below) - just adapt the namespace to your
ExtensionInformation class.
Look into the configuration classes to see all available options and their default values.

- Classes/Data/ExtensionInformation.php
  ```php
  public function __construct()
    {
        parent::__construct();
        $this->addMainModule(GeneralUtility::makeInstance(MainModuleConfiguration::class,
            key: 'my_main_module',
            position: ['after' => 'web']
        ));
        $this->addModule(GeneralUtility::makeInstance(ModuleConfiguration::class,
            controllers: [MyController::class],
            key: 'my_module',
            parent: 'my_main_module'
        ));
    }
  ```

- Configuration/Backend/Modules.php
  ```php
  <?php
  declare(strict_types=1);
  
  use PSBits\Foundation\Data\ExtensionInformation; // <-- Change this to your namespace!
  use PSBits\Foundation\Service\Configuration\ModuleService;
  use TYPO3\CMS\Core\Utility\GeneralUtility;
  
  return GeneralUtility::makeInstance(ModuleService::class)
  ->buildModuleConfiguration(GeneralUtility::makeInstance(ExtensionInformation::class));

  ```

- Classes/Controller/YourModuleController.php
  ```php
  use PSBits\Foundation\Attribute\ModuleAction;
  use PSBits\Foundation\Controller\Backend\AbstractModuleController;

  class YourModuleController extends AbstractModuleController
  {
      #[ModuleAction(default: true)]
      public function mainAction(): ResponseInterface
      {
          ...
  
          return $this->htmlResponse();
      }
  
      #[ModuleAction]
      public function simpleAction(): ResponseInterface
      {
          ...
  
          return $this->htmlResponse();
      }
  }
  ```

The AbstractModuleController class contains some basic template preparations which allow you to render your template in
the same way as in plugin controllers: `return $this->htmlResponse()`!

Modules need to provide three labels:

| Label                 | Description                                                  |
|-----------------------|--------------------------------------------------------------|
| mlang_labels_tabdescr | used as module description in the about-module               |
| mlang_labels_tablabel | used as short description when hovering over the module link |
| mlang_tabs_tab        | used as module title                                         |

The following fallbacks account for main modules and submodules, if no custom value is specified:

- language file: `EXT:your_extension/Resources/Private/Language/Backend/Modules/[moduleName].xlf:` (file name starts
  with lower case!)
- icon identifier: `extension-key-module-your-module-name` (i.e. your filename would be `module-your-module-name`!)

Fallbacks regarding submodules only:

- access: `group, user`
- main module: `web`

Check the other configuration options and comments in `EXT:foundation/Classes/Attributes/`.

### Registering custom page types

Classes/Data/ExtensionInformation.php

```php
public function __construct()
{
    parent::__construct();
    $this->addPageType(GeneralUtility::makeInstance(PageTypeConfiguration::class,
        allowedTables: ['*'],
        iconIdentifier: 'page-type-your-page-type-name',
        label: 'Your page type name',
        name: 'yourPageTypeName',
        doktype: 1691492222,
    ));
}
```

The keys (doktype) have to be of type integer. `name` is the only mandatory value.
If you don't provide an icon identifier this default identifier will be used: `page-type-your-page-type-name`.
The identifier is also used as base for further icon-variants.

Example (`'name' => 'custom'`):

- page-type-custom
- page-type-custom-contentFromPid
- page-type-custom-hideinmenu
- page-type-custom-root

You don't have to provide all these icons. The icons for regular pages will be used as fallback.
Your SVG-files should to be located in this directory: `EXT:your_extension/Resources/Public/Icons/`
All icons in that directory will be registered by their name automatically.
Unless `label` is defined,
`EXT:your_extension/Resources/Private/Language/Backend/Configuration/TCA/Overrides/page.xlf:pageType.yourPageTypeName`
will be used.
If that key doesn't exist, `name` will be transformed from "yourPageTypeName" to "Your page type name".

### Auto-registration of TypoScript-files

If there are `.typoscript`-files located in `EXT:your_extension/Configuration/TypoScript]`, foundation will execute
`\PSBits\Foundation\Utility\TypoScript\TypoScriptUtility::registerTypoScript()` for that directory.
You can provide a custom title for the select item in the template module with
`EXT:your_extension/Resources/Private/Language/Backend/Configuration/TCA/Overrides/sys_template.xlf:template.title` -
defaults to `'Main configuration'`.

### Auto-registration of TSconfig-files

If this file exists, it will be included automatically:

- `EXT:your_extension/Configuration/User.tsconfig` (You can use 'User' or 'user')

The core already handles the inclusion of

- `EXT:your_extension/Configuration/Page.tsconfig` (You can use 'Page' or 'page')
  https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-96614-AutomaticInclusionOfPageTsConfigOfExtensions.htm

### Auto-registration of icons

All PNG- and SVG-files located in `EXT:your_extension/Resources/Public/Icons` will be registered automatically.
The extension key and the file name are used as icon identifier.

Examples (filename => icon identifier):

- `logo.svg` => `your-extension-key-logo`
- `MainMenu.png` => `your-extension-key-main-menu`

### Extension settings

#### Log missing language labels

If activated, missing language labels will be stored in `tx_foundation_missing_language_labels`.
All missing default labels (e.g. plugin title or field label) will be listed this way if you didn't provide a custom
label.
Fixed entries get removed on next check (every time the cache is cleared).<br>
It's recommended to check this table during extension development.

### Helper classes

#### ContextUtility

`PSBits\Foundation\Utility\ContextUtility` offers short and easy access to basic information.

- getCurrentLocale
- isBackend
- isBootProcessRunning
- isFrontend
- isTypoScriptAvailable

#### GlobalVariableService

`PSBits\Foundation\Service\GlobalVariableService` allows easy and performant access to often needed data.
This service is a container where data providers can be registered by their class name.
Providers are instantiated only when they are accessed (and then only once).
Data that no longer changes can be cached within the GlobalVariableService for the current request.

It's possible to register own providers that implement
`PSBits\Foundation\Service\GlobalVariableProviders\GlobalVariableProviderInterface`.
You can extend `PSBits\Foundation\Service\GlobalVariableProviders\AbstractProvider`.
Three providers are shipped within this extension.

- `PSBits\Foundation\Service\GlobalVariableProviders\RequestParameterProvider`: returns processed GET and POST
  parameters. The two arrays are merged (POST would overwrite GET) and all values are sanitized first and then converted
  by StringUtility::convertString().
- `PSBits\Foundation\Service\GlobalVariableProviders\SiteConfigurationProvider`: returns the SiteConfiguration using the
  SiteFinder.
- `PSBits\Foundation\Service\GlobalVariableProviders\EarlyAccessConstantsProvider`: gives you the possibility to define
  constants that you can access very early during bootstrap - before TypoScript is loaded. Have a look at the comments
  in this class!

Examples:

```php
// get SiteConfiguration
GlobalVariableService::get(SiteConfigurationProvider::class);

// get all request parameters
GlobalVariableService::get(RequestParameterProvider::class);

// get specific request parameter
GlobalVariableService::get(RequestParameterProvider::class . '.formData.hiddenInput');
```

#### StringUtility

`PSBits\Foundation\Utility\StringUtility` contains some string manipulation functions, e.g.:

- convertString: performs a type cast or other operations based on the string's content, e.g.<br>

  | input value                                       | return type                                                                                 |
    |---------------------------------------------------|---------------------------------------------------------------------------------------------|
  | (empty string)                                    | empty string will be returned as empty string or null (depends on second argument)          |
  | `0`, `123`                                        | returns integer                                                                             |
  | `0.1`, `0,1`                                      | returns float                                                                               |
  | `0001423`                                         | returns unchanged string                                                                    |
  | `TS:config.headerComment`                         | returns value from TypoScript (if path is valid) which is also processed by this function.  |
  | `\Full\Qualified\ClassName::CONSTANT['arrayKey']` | returns value of constant which is also processed by this function.                         |
  | `{...}`, `[...]`                                  | returns array if valid JSON                                                                 |
  | `false`, `true`                                   | returns boolean                                                                             |

    - returns the string if no other format could be identified
- explodeByLineBreaks: can be used to get the lines of a file or text field as array
- getNumberFormatter: returns a NumberFormatter based on the current locale

#### TranslateViewHelper

`PSBits\Foundation\ViewHelpers\TranslateViewHelper` is an extended clone of the core's TranslateViewHelper.

Additional features:

- Support of plural forms in language files;
  <trans-unit>-tags in xlf-files can be grouped like this to define plural forms of a translation:
  ```xml
  <group id="day" restype="x-gettext-plurals">
    <trans-unit id="day[0]">
      <source>{0} day</source>
    </trans-unit>
    <trans-unit id="day[1]">
      <source>{0} days</source>
    </trans-unit>
  </group>
  ```
  The number in [] defines the plural form as defined here:
  http://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html
  See `\PSBits\Foundation\Utility\Localization\PluralFormUtility` for more information.
  In order to use the plural forms defined in your language files, you have to transfer an argument named '
  quantity':<br>
  `<psbits:translate arguments="{quantity: 1}" id="..." />`<br>
  This argument can be combined with others (see support of named arguments below).
- Named arguments: a more convenient way to pass variables into translations<br>
  Instead of:
  ```html
  <!-- Template file -->
  <f:translate arguments="{0: 'myVar', 1: 123}" id="myLabel" />
  <!-- Language file -->
  <source>My two variables are %1$s and %2$s.</source>
  ```
  you can use:
  ```html
  <!-- Template file -->
  <psbits:translate arguments="{myVar: 'myVar', anotherVar: 123} id="myLabel" />
  <!-- Language file -->
  <source>My two variables are {myVar} and {anotherVar}.</source>
  ```
  If a variable is not passed, the marker will remain untouched!
- New attribute "excludedLanguages"<br>
  matching language keys will return null (bypasses fallbacks!)
  This way you can remove texts from certain site languages without additional condition wrappers in your template.

#### TypoScriptProviderService

`PSBits\Foundation\Service\TypoScriptProviderService` offers a convenient way to retrieve specific TypoScript-settings.
As first argument you can provide the array path you want to access. The following arguments are the same as for the
ConfigurationManager known from Extbase.

Examples:

```php
// get all TypoScript
$this->typoScriptProviderService->get();

// get specific part
$this->typoScriptProviderService->get('config');

// get all settings from extension
$this->typoScriptProviderService->get(null, ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'extensionName', 'pluginName');

// get specific setting from extension
$this->typoScriptProviderService->get('displayOptions.showPreview', ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'extensionName', 'pluginName');
```

The values returned by this service have been converted to the correct type already (using StringUtility::
convertString()).
If you set `displayOptions.showPreview = 1` the last example will return an integer.
If you set `displayOptions.showPreview = true` it will return a boolean.
Not set constants will be returned as `null` instead of `{$...}`.

#### UploadService

This service provides one method that should be called from a controller action which handles a form submit:

```php
public function fromRequest(AbstractEntity $domainModel, Request $request): void
```

This method receives a domain model instance to associate the uploaded files with and an Extbase request object.
It does not have a return value, but will throw an exception if something goes wrong.
You will have to handle those cases in your code!

> The requirement for the processing of uploaded files is that the name of the file input fields of the form match the
> corresponding properties' name!

By default the files will be stored in fileadmin/user_upload/ by their original name as sent by the client (all special
characters being removed).
This can be changed via TCA:

```php
#[File(
    allowed: [
        'jpeg',
        'jpg',
    ],
    maxItems: 1,
    uploadDuplicationBehaviour: DuplicationBehavior::REPLACE,
    uploadFileNameGeneratorPartSeparator: '_',
    uploadFileNameGeneratorPrefix: 'myDomainModel',
    uploadFileNameGeneratorProperties: ['uid'],
    uploadFileNameGeneratorSuffix: 'image',
    uploadTargetFolder: 'user_upload/my_extension',
)]
protected ?FileReference $image = null;

#[File(
    allowed: [
        'pdf',
    ],
    uploadFileNameGeneratorAppendHash: true,
    uploadFileNameGeneratorProperties: ['category.name', 'title'],
    uploadFileNameGeneratorReplacements: [
        ' ' => '_',
    ],
    uploadTargetFolder: '2:my_extension/my_domain_model/documents',
)]
protected ObjectStorage $documents;
```

See comments in class constructor for more information on certain options.
