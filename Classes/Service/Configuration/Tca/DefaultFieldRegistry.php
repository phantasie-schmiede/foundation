<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DefaultFieldRegistry
 *
 * Keeps track of the default showitem entries (tabs, palettes and fields) that are added to the showitems of all
 * types after the position-dependencies of the columns have been resolved. It resolves position references to these
 * entries and materializes their blocks on demand.
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
class DefaultFieldRegistry
{
    /**
     * @var list<DefaultFieldDefinition>
     */
    private array $definitions;

    /**
     * @var array<string, bool>
     */
    private array $materializedBlocks = [];

    /**
     * @param list<DefaultFieldDefinition> $definitions
     */
    public function __construct(
        private readonly TcaTable $table,
        array                     $definitions,
    ) {
        $this->definitions = $definitions;
    }

    /**
     * Returns the definition matching the given reference, or null if there is none. Since position references are
     * normalized to column names, the underscored variant of the reference name is matched as well
     * (e.g. "time_restriction" matches "timeRestriction").
     */
    public function findByReference(string $reference): ?DefaultFieldDefinition
    {
        foreach ($this->definitions as $definition) {
            $definitionReference = $definition->getReference();

            if ($definitionReference === $reference || GeneralUtility::camelCaseToLowerCaseUnderscored(
                $definitionReference
            ) === $reference) {
                return $definition;
            }
        }

        return null;
    }

    public function isBlockMaterialized(string $block): bool
    {
        return isset($this->materializedBlocks[$block]);
    }

    /**
     * Adds all items of the given block to the showitems of all types, in definition order.
     */
    public function materializeBlock(string $block): void
    {
        if (true === $this->isBlockMaterialized($block)) {
            return;
        }

        foreach ($this->definitions as $definition) {
            if ($definition->getBlock() === $block) {
                $this->table->addFieldsToAllTypes($definition->getItem());
            }
        }

        $this->materializedBlocks[$block] = true;
    }
}
