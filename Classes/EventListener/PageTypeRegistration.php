<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\EventListener;

use InvalidArgumentException;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\Configuration\PageTypeService;
use PSBits\Foundation\Service\ExtensionInformationService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

#[AsEventListener(identifier: 'foundation_pageTypeRegistration')]
final readonly class PageTypeRegistration
{
    public function __construct(
        protected ExtensionInformationService $extensionInformationService,
        protected PageTypeService             $pageTypeService,
    ) {
    }

    /**
     * @throws ImplementationException
     * @throws InvalidArgumentException
     */
    public function __invoke(BootCompletedEvent $event): void
    {
        /*
         * The registry initializes its default allowed tables from the loaded TCA schemata.
         * Registering from ext_localconf.php would permanently initialize it with an empty schema list.
         */
        foreach ($this->extensionInformationService->getAllExtensionInformation() as $extensionInformation) {
            $this->pageTypeService->addToRegistry($extensionInformation);
        }
    }
}
