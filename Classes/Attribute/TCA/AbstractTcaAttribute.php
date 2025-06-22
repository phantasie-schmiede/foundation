<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA;

use PSBits\Foundation\Attribute\AbstractAttribute;
use PSBits\Foundation\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractTcaAttribute
 *
 * @package PSBits\Foundation\Attribute\TCA
 */
abstract class AbstractTcaAttribute extends AbstractAttribute
{
    protected TcaService $tcaService;

    public function __construct()
    {
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
    }
}
