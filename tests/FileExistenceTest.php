<?php

namespace Detain\MyAdminDirectadmin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests that verify the package file structure is complete and correct.
 *
 * Ensures all required files exist, are non-empty, and have correct PHP syntax
 * where applicable. This catches packaging errors and accidental deletions.
 */
class FileExistenceTest extends TestCase
{
    /**
     * @var string Absolute path to the package root directory
     */
    private static $packageRoot;

    public static function setUpBeforeClass(): void
    {
        self::$packageRoot = dirname(__DIR__);
    }

    /**
     * Tests that the composer.json file exists.
     * Required for Composer autoloading and dependency resolution.
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists(self::$packageRoot . '/composer.json');
    }

    /**
     * Tests that composer.json contains valid JSON.
     * Invalid JSON would break Composer operations entirely.
     */
    public function testComposerJsonIsValidJson(): void
    {
        $content = file_get_contents(self::$packageRoot . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'composer.json should contain valid JSON');
    }

    /**
     * Tests that composer.json declares the correct package name.
     * The name is used by Composer for dependency resolution.
     */
    public function testComposerJsonHasCorrectName(): void
    {
        $content = json_decode(file_get_contents(self::$packageRoot . '/composer.json'), true);
        $this->assertSame('detain/myadmin-directadmin-licensing', $content['name']);
    }

    /**
     * Tests that composer.json declares PSR-4 autoloading for the correct namespace.
     * Without this, the Plugin class cannot be autoloaded.
     */
    public function testComposerJsonHasPsr4Autoload(): void
    {
        $content = json_decode(file_get_contents(self::$packageRoot . '/composer.json'), true);
        $this->assertArrayHasKey('autoload', $content);
        $this->assertArrayHasKey('psr-4', $content['autoload']);
        $this->assertArrayHasKey('Detain\\MyAdminDirectadmin\\', $content['autoload']['psr-4']);
        $this->assertSame('src/', $content['autoload']['psr-4']['Detain\\MyAdminDirectadmin\\']);
    }

    /**
     * Tests that the src/ directory exists.
     * All source code should reside in this directory.
     */
    public function testSrcDirectoryExists(): void
    {
        $this->assertDirectoryExists(self::$packageRoot . '/src');
    }

    /**
     * Tests that the Plugin.php source file exists.
     * This is the main class file for the MyAdmin plugin.
     */
    public function testPluginPhpExists(): void
    {
        $this->assertFileExists(self::$packageRoot . '/src/Plugin.php');
    }

    /**
     * Tests that the directadmin.inc.php source file exists.
     * This file contains all procedural DirectAdmin API functions.
     */
    public function testDirectadminIncPhpExists(): void
    {
        $this->assertFileExists(self::$packageRoot . '/src/directadmin.inc.php');
    }

    /**
     * Tests that README.md exists.
     * Documentation is essential for open-source packages.
     */
    public function testReadmeExists(): void
    {
        $this->assertFileExists(self::$packageRoot . '/README.md');
    }

    /**
     * Tests that Plugin.php is not empty.
     * An empty file would indicate corruption or accidental truncation.
     */
    public function testPluginPhpIsNotEmpty(): void
    {
        $content = file_get_contents(self::$packageRoot . '/src/Plugin.php');
        $this->assertNotEmpty(trim($content));
    }

    /**
     * Tests that directadmin.inc.php is not empty.
     * An empty file would indicate corruption or accidental truncation.
     */
    public function testDirectadminIncPhpIsNotEmpty(): void
    {
        $content = file_get_contents(self::$packageRoot . '/src/directadmin.inc.php');
        $this->assertNotEmpty(trim($content));
    }

    /**
     * Tests that Plugin.php declares the correct namespace.
     * A wrong namespace would prevent autoloading from working.
     */
    public function testPluginPhpHasCorrectNamespace(): void
    {
        $content = file_get_contents(self::$packageRoot . '/src/Plugin.php');
        $this->assertStringContainsString('namespace Detain\\MyAdminDirectadmin;', $content);
    }

    /**
     * Tests that Plugin.php defines the Plugin class.
     * This is the expected class name based on the filename.
     */
    public function testPluginPhpDefinesPluginClass(): void
    {
        $content = file_get_contents(self::$packageRoot . '/src/Plugin.php');
        $this->assertStringContainsString('class Plugin', $content);
    }

    /**
     * Tests that the bin/ directory exists.
     * Contains CLI scripts for direct DirectAdmin operations.
     */
    public function testBinDirectoryExists(): void
    {
        $this->assertDirectoryExists(self::$packageRoot . '/bin');
    }

    /**
     * Tests that the composer.json declares the LGPL-2.1 license.
     * The license field is required for Packagist compliance.
     */
    public function testComposerJsonHasLicense(): void
    {
        $content = json_decode(file_get_contents(self::$packageRoot . '/composer.json'), true);
        $this->assertArrayHasKey('license', $content);
        $this->assertSame('LGPL-2.1-only', $content['license']);
    }

    /**
     * Tests that composer.json requires PHP.
     * Ensures minimum PHP version is declared for dependency resolution.
     */
    public function testComposerJsonRequiresPhp(): void
    {
        $content = json_decode(file_get_contents(self::$packageRoot . '/composer.json'), true);
        $this->assertArrayHasKey('php', $content['require']);
    }

    /**
     * Tests that composer.json requires the curl extension.
     * DirectAdmin API calls use cURL extensively.
     */
    public function testComposerJsonRequiresCurl(): void
    {
        $content = json_decode(file_get_contents(self::$packageRoot . '/composer.json'), true);
        $this->assertArrayHasKey('ext-curl', $content['require']);
    }

    /**
     * Tests that composer.json has phpunit in require-dev.
     * PHPUnit is needed to run the test suite.
     */
    public function testComposerJsonHasPhpunitInRequireDev(): void
    {
        $content = json_decode(file_get_contents(self::$packageRoot . '/composer.json'), true);
        $this->assertArrayHasKey('require-dev', $content);
        $this->assertArrayHasKey('phpunit/phpunit', $content['require-dev']);
    }
}
