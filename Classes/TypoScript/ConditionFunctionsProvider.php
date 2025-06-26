<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\TypoScript;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConditionFunctionsProvider
 *
 * @package PSBits\Foundation\TypoScript
 */
class ConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            $this->getExtensionLoadedCondition(),
            $this->getFileExistsCondition(),
        ];
    }

    protected function getExtensionLoadedCondition(): ExpressionFunction
    {
        return new ExpressionFunction('extensionLoaded', static function() {
            // Not implemented, we only use the evaluator
        }, static function($variables, $extensionKey) {
            return ExtensionManagementUtility::isLoaded($extensionKey);
        });
    }

    protected function getFileExistsCondition(): ExpressionFunction
    {
        return new ExpressionFunction('fileExists', static function() {
            // Not implemented, we only use the evaluator
        }, static function($variables, $filePath) {
            $filePath = GeneralUtility::getFileAbsFileName($filePath);

            return file_exists($filePath);
        });
    }
}
