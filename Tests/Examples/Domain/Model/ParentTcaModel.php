<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ParentTcaModel
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
class ParentTcaModel extends AbstractEntity
{
    protected string $parentProperty = '';

    public function getParentProperty(): string
    {
        return $this->parentProperty;
    }

    public function setParentProperty(string $parentProperty): void
    {
        $this->parentProperty = $parentProperty;
    }
}
