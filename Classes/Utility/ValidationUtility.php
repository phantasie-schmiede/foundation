<?php
declare(strict_types=1);

/*
 * This file is part of PSB Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbFoundation\Utility;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use function in_array;

/**
 * Class ValidationUtility
 *
 * @package PSB\PsbFoundation\Utility
 */
class ValidationUtility
{
    public static function checkArrayAgainstConstantKeys(array $constant, array $keys): void
    {
        foreach ($keys as $key) {
            self::checkKeyAgainstConstant($constant, $key);
        }
    }

    public static function checkArrayAgainstConstantValues(array $constant, array $values): void
    {
        foreach ($values as $value) {
            self::checkValueAgainstConstant($constant, $value);
        }
    }

    public static function checkKeyAgainstConstant(array $constant, string $key): void
    {
        if (!isset($constant[$key])) {
            throw new InvalidArgumentException(
                __CLASS__ . ': Key "' . $key . '" is not present in constant!', 1564122378
            );
        }
    }

    public static function checkValueAgainstConstant(array $constant, mixed $value): void
    {
        if (!in_array($value, $constant, true)) {
            throw new InvalidArgumentException(
                __CLASS__ . ': Value "' . $value . '" is not present in constant!', 1564068237
            );
        }
    }

    public static function requiresBackendContext(): void
    {
        if (!ContextUtility::isBackend()) {
            throw new RuntimeException(__CLASS__ . ': This method is allowed in backend context only!', 1614416247);
        }
    }

    public static function requiresFrontendContext(): void
    {
        if (!ContextUtility::isFrontend()) {
            throw new RuntimeException(__CLASS__ . ': This method is allowed in frontend context only!', 1614416275);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function requiresTypoScriptLoaded(): void
    {
        if (!ContextUtility::isTypoScriptAvailable()) {
            throw new RuntimeException(
                __CLASS__ . ': This method is not allowed during the bootstrap process of TYPO3! Do not call it within or from ext_localconf.php',
                1727172047
            );
        }
    }
}
