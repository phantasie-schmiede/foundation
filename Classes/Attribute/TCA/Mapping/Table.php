<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA\Mapping;

use Attribute;

/**
 * Class Table
 *
 * Use this attribute to map a domain model to a table which does not convey to the naming convention, e. g. when
 * extending another model.
 *
 * @package PSBits\Foundation\Attribute\TCA\Mapping
 * @see     https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#use-arbitrary-database-tables-with-an-extbase-model
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    /**
     * @param string          $name
     * @param string|null     $parentClass If parent class is given, the model will be added to that class' subclasses.
     * @param int|string|null $recordType
     */
    public function __construct(
        protected string          $name,
        protected string|null     $parentClass = null,
        protected int|string|null $recordType = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentClass(): ?string
    {
        return $this->parentClass;
    }

    public function getRecordType(): int|string|null
    {
        return $this->recordType;
    }
}
