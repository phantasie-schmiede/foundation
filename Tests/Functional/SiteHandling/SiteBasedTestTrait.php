<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Functional\SiteHandling;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;

/**
 * Trait SiteBasedTestTrait
 *
 * Writes site configuration files for functional tests.
 *
 * This is a cut-down version of the identically named trait from typo3/cms-core. The core package
 * marks its sysext test directories as export-ignore, so its test classes are not shipped in the
 * distributed package and cannot be autoloaded by an extension that installs core from dist, which
 * is the default. Everything the core trait offers beyond writing a site configuration is frontend
 * request instruction handling and only relevant to tests inside core itself.
 *
 * The configuration is serialised and written directly instead of through SiteWriter, which is not
 * available on every supported major: the class does not exist in v12 and only became a public
 * service later on. SiteConfiguration::load() reads the very same config.yaml format on all
 * supported versions, so writing it here keeps the trait free of version specific wiring.
 *
 * @package PSBits\Foundation\Tests\Functional\SiteHandling
 */
trait SiteBasedTestTrait
{
    protected function buildSiteConfiguration(int $rootPageId, string $base = ''): array
    {
        return [
            'rootPageId' => $rootPageId,
            'base'       => $base,
        ];
    }

    protected function writeSiteConfiguration(
        string $identifier,
        array  $site = [],
        array  $languages = [],
        array  $errorHandling = [],
        array  $dependencies = [],
    ): void {
        $configuration = $site;

        if ([] !== $languages) {
            $configuration['languages'] = $languages;
        }

        if ([] !== $errorHandling) {
            $configuration['errorHandling'] = $errorHandling;
        }

        if ([] !== $dependencies) {
            $configuration['dependencies'] = $dependencies;
        }

        // A leftover configuration from a previous run would silently win, so remove it first.
        $path = Environment::getConfigPath() . '/sites/' . $identifier;
        GeneralUtility::rmdir($path, true);
        GeneralUtility::mkdir_deep($path);
        file_put_contents($path . '/config.yaml', $this->dumpYaml($configuration));
    }

    /**
     * Renders a site configuration as YAML. A dumper is used on purpose: symfony/yaml is only a
     * transitive dependency of the core packages and its availability differs between the supported
     * majors. The structures written here are plain lists and maps of scalars.
     */
    private function dumpYaml(array $data, int $level = 0): string
    {
        if ([] === $data) {
            return "{}\n";
        }

        $isList = array_is_list($data);
        $indent = str_repeat('  ', $level);

        $yaml = '';

        foreach ($data as $key => $value) {
            $prefix = $isList ? $indent . '-' : $indent . $key . ':';

            if (is_array($value)) {
                $yaml .= [] === $value ? $prefix . " {}\n" : $prefix . "\n" . $this->dumpYaml($value, $level + 1);

                continue;
            }
            $yaml .= $prefix . ' ' . $this->dumpYamlScalar($value) . "\n";
        }

        return $yaml;
    }

    private function dumpYamlScalar(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        return "'" . str_replace("'", "''", (string)$value) . "'";
    }
}
