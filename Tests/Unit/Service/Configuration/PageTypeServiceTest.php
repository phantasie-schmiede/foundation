<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Unit\Service\Configuration;

use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Exception as FrameworkException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PSBits\Foundation\Data\ExtensionInformation;
use PSBits\Foundation\Data\PageTypeConfiguration;
use PSBits\Foundation\Service\Configuration\PageTypeService;
use PSBits\Foundation\Utility\Typo3VersionUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PageTypeServiceTest
 *
 * Covers the page type registration that used to live in ext_tables.php. That file is not loaded
 * anymore since v13, so the registration now runs from ext_localconf.php and has to keep working
 * on every supported core version.
 *
 * @package PSBits\Foundation\Tests\Unit\Service\Configuration
 */
class PageTypeServiceTest extends UnitTestCase
{
    private PageTypeService $subject;

    /**
     * On v13 the TSconfig call is the only way to get the doktype into the drag area, so the page
     * types have to be read (that core call emits a deprecation in v13). On v14 the core determines
     * the drag area automatically, so nothing is read.
     *
     * @throws Exception
     * @throws IncompatibleReturnValueException
     * @throws InvalidArgumentException
     * @throws MethodCannotBeConfiguredException
     * @throws MethodNameAlreadyConfiguredException
     * @throws NoPreviousThrowableException
     */
    #[Test]
    #[IgnoreDeprecations]
    public function addToDragAreaReadsThePageTypes(): void
    {
        $extensionInformation = $this->createMock(ExtensionInformation::class);

        if (Typo3VersionUtility::isAtLeast('14.0')) {
            $extensionInformation->expects(self::never())
                ->method('getPageTypes');
        } else {
            $extensionInformation->expects(self::once())
                ->method('getPageTypes')
                ->willReturn([new PageTypeConfiguration(doktype: 4444, name: 'dragArea')]);
        }

        $this->subject->addToDragArea($extensionInformation);
    }

    /**
     * @throws Exception
     * @throws FrameworkException
     * @throws IncompatibleReturnValueException
     * @throws InvalidArgumentException
     * @throws MethodCannotBeConfiguredException
     * @throws MethodNameAlreadyConfiguredException
     * @throws NoPreviousThrowableException
     */
    #[Test]
    public function addToRegistryRegistersAnUnrestrictedDoktypeWithoutExtraConfiguration(): void
    {
        $pageDoktypeRegistry = $this->createMock(\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry::class);

        // A page type without allowedTables must not receive an empty allowedTables configuration.
        $pageDoktypeRegistry->expects(self::once())
            ->method('add')
            ->with(4343, []);

        $extensionInformation = $this->createMock(ExtensionInformation::class);
        $extensionInformation->method('getPageTypes')
            ->willReturn([new PageTypeConfiguration(doktype: 4343, name: 'unrestricted')]);

        (new PageTypeService(
            $pageDoktypeRegistry
        ))->addToRegistry($extensionInformation);
    }

    /**
     * @throws Exception
     * @throws FrameworkException
     * @throws IncompatibleReturnValueException
     * @throws InvalidArgumentException
     * @throws MethodCannotBeConfiguredException
     * @throws MethodNameAlreadyConfiguredException
     * @throws NoPreviousThrowableException
     */
    #[Test]
    public function addToRegistryRegistersTheDoktypeWithItsAllowedTables(): void
    {
        $extensionInformation = $this->createMock(ExtensionInformation::class);
        $pageType             = new PageTypeConfiguration(
            doktype: 4242,
            name: 'overlayPage',
            allowedTables: [
                'pages',
                'tt_content',
            ]
        );

        $extensionInformation->expects(self::once())
            ->method('getPageTypes')
            ->willReturn([$pageType]);

        $pageDoktypeRegistry = $this->createMock(\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry::class);
        $pageDoktypeRegistry->expects(self::once())
            ->method('add')
            ->with(
                4242,
                [
                    'allowedTables'     => 'pages,tt_content',
                    'onlyAllowedTables' => true,
                ]
            );

        (new PageTypeService(
            $pageDoktypeRegistry
        ))->addToRegistry($extensionInformation);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NoPreviousThrowableException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PageTypeService(
            $this->createMock(\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry::class)
        );
    }
}
