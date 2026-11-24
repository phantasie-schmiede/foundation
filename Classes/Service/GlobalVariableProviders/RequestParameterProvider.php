<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\GlobalVariableProviders;

use Error;
use InvalidArgumentException;
use JsonException;
use PSBits\Foundation\Utility\ContextUtility;
use PSBits\Foundation\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;

use function is_array;
use function is_string;

class RequestParameterProvider extends AbstractProvider
{
    /**
     * @return array<string, mixed>
     * @throws ContainerExceptionInterface
     * @throws Error
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getGlobalVariables(): array
    {
        $request        = ContextUtility::getRequest();
        $parameters     = $request?->getQueryParams() ?? [];
        $postParameters = $request?->getParsedBody();

        if (is_array($postParameters)) {
            ArrayUtility::mergeRecursiveWithOverrule($parameters, $postParameters);
        }

        array_walk_recursive($parameters, static function(&$item) {
            $item = is_string($item) ? StringUtility::convertString($item) : $item;
        });

        return $parameters;
    }
}
