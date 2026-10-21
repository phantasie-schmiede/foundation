<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * The copyright and license information can be found in the file LICENSE.txt,
 * which was distributed with this source code.
 */

namespace PSBitsTest\Tcafixture\Domain\Model;

use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class MacGuffin
 *
 * @package PSBitsTest\Tcafixture\Domain\Model
 */
#[Ctrl]
class MacGuffin extends AbstractEntity
{
    #[Input]
    protected string $name = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
