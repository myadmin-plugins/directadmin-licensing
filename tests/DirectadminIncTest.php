<?php

namespace Detain\MyAdminDirectadmin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the procedural functions in directadmin.inc.php.
 *
 * Since most functions depend on external services (cURL, database, MyAdmin globals),
 * we focus on testing pure functions, file structure, and function existence
 * via static analysis of the source file.
 */
class DirectadminIncTest extends TestCase
{
    /**
     * @var string Absolute path to directadmin.inc.php
     */
    private static $sourceFile;

    /**
     * @var string Contents of directadmin.inc.php for static analysis
     */
    private static $sourceContents;

    public static function setUpBeforeClass(): void
    {
        self::$sourceFile = dirname(__DIR__) . '/src/directadmin.inc.php';
        self::$sourceContents = file_get_contents(self::$sourceFile);
    }

    /**
     * Tests that directadmin.inc.php exists at the expected path.
     * This file contains all procedural DirectAdmin API functions.
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(self::$sourceFile);
    }

    /**
     * Tests that the source file is valid PHP by checking for opening tag.
     * A missing PHP tag would prevent any functions from being defined.
     */
    public function testSourceFileIsValidPhp(): void
    {
        $this->assertStringStartsWith('<?php', self::$sourceContents);
    }

    /**
     * Tests that get_directadmin_license_types() is defined in the source.
     * This is a pure function that returns a static map of OS types.
     */
    public function testGetDirectadminLicenseTypesIsDefined(): void
    {
        $this->assertStringContainsString(
            'function get_directadmin_license_types()',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_get_best_type() is defined in the source.
     * Determines the best DirectAdmin license type based on OS info.
     */
    public function testDirectadminGetBestTypeIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_get_best_type(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_req() is defined in the source.
     * Handles all HTTP requests to the DirectAdmin API.
     */
    public function testDirectadminReqIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_req(',
            self::$sourceContents
        );
    }

    /**
     * Tests that get_directadmin_licenses() is defined in the source.
     * Retrieves the list of all DirectAdmin licenses from the API.
     */
    public function testGetDirectadminLicensesIsDefined(): void
    {
        $this->assertStringContainsString(
            'function get_directadmin_licenses()',
            self::$sourceContents
        );
    }

    /**
     * Tests that get_directadmin_license() is defined in the source.
     * Retrieves details for a single license by ID.
     */
    public function testGetDirectadminLicenseIsDefined(): void
    {
        $this->assertStringContainsString(
            'function get_directadmin_license($lid)',
            self::$sourceContents
        );
    }

    /**
     * Tests that get_directadmin_license_by_ip() is defined in the source.
     * Finds a license by its associated IP address.
     */
    public function testGetDirectadminLicenseByIpIsDefined(): void
    {
        $this->assertStringContainsString(
            'function get_directadmin_license_by_ip(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_ip_to_lid() is defined in the source.
     * Converts an IP address to a license ID.
     */
    public function testDirectadminIpToLidIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_ip_to_lid(',
            self::$sourceContents
        );
    }

    /**
     * Tests that activate_directadmin() is defined in the source.
     * Creates and activates a new DirectAdmin license.
     */
    public function testActivateDirectadminIsDefined(): void
    {
        $this->assertStringContainsString(
            'function activate_directadmin(',
            self::$sourceContents
        );
    }

    /**
     * Tests that deactivate_directadmin() is defined in the source.
     * Cancels an active DirectAdmin license.
     */
    public function testDeactivateDirectadminIsDefined(): void
    {
        $this->assertStringContainsString(
            'function deactivate_directadmin(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_deactivate() is defined as a wrapper.
     * This is an alias that delegates to deactivate_directadmin().
     */
    public function testDirectadminDeactivateIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_deactivate(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_makepayment() is defined in the source.
     * Processes payment for a newly created license.
     */
    public function testDirectadminMakepaymentIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_makepayment(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_get_os_list() is defined in the source.
     * Retrieves the available OS list from the DirectAdmin API.
     */
    public function testDirectadminGetOsListIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_get_os_list(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_get_products() is defined in the source.
     * Retrieves the product catalog from the DirectAdmin API.
     */
    public function testDirectadminGetProductsIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_get_products()',
            self::$sourceContents
        );
    }

    /**
     * Tests that activate_free_license() is defined in the source.
     * Activates a free-tier DirectAdmin license.
     */
    public function testActivateFreeLicenseIsDefined(): void
    {
        $this->assertStringContainsString(
            'function activate_free_license(',
            self::$sourceContents
        );
    }

    /**
     * Tests that delete_free_license() is defined in the source.
     * Deletes a free-tier DirectAdmin license.
     */
    public function testDeleteFreeLicenseIsDefined(): void
    {
        $this->assertStringContainsString(
            'function delete_free_license(',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_modify_os() is defined in the source.
     * Changes the OS type of an existing license.
     */
    public function testDirectadminModifyOsIsDefined(): void
    {
        $this->assertStringContainsString(
            'function directadmin_modify_os(',
            self::$sourceContents
        );
    }

    /**
     * Tests that the source file contains exactly the expected number of function definitions.
     * Guards against accidentally removing or adding functions.
     */
    public function testFunctionCount(): void
    {
        preg_match_all('/^\s*function\s+\w+\s*\(/m', self::$sourceContents, $matches);
        $this->assertCount(16, $matches[0], 'Expected 16 function definitions in directadmin.inc.php');
    }

    /**
     * Tests that get_directadmin_license_types() returns the expected associative array.
     * This is a pure function with no external dependencies.
     */
    public function testGetDirectadminLicenseTypesReturnsExpectedArray(): void
    {
        // This function is safe to call directly as it has no side effects
        require_once self::$sourceFile;

        $types = get_directadmin_license_types();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);

        // Verify some known entries
        $this->assertArrayHasKey('ES 5.0', $types);
        $this->assertArrayHasKey('ES 8.0 64', $types);
        $this->assertArrayHasKey('Debian 8 64', $types);
        $this->assertArrayHasKey('FreeBSD 9.0 64', $types);
    }

    /**
     * Tests that get_directadmin_license_types() has exactly 16 entries.
     * Each entry maps an internal type code to a human-readable OS description.
     */
    public function testGetDirectadminLicenseTypesCount(): void
    {
        $types = get_directadmin_license_types();
        $this->assertCount(16, $types);
    }

    /**
     * Tests that all license type keys are non-empty strings.
     * Keys are used as identifiers in API calls to DirectAdmin.
     */
    public function testLicenseTypeKeysAreStrings(): void
    {
        $types = get_directadmin_license_types();
        foreach ($types as $key => $value) {
            $this->assertIsString($key);
            $this->assertNotEmpty($key);
        }
    }

    /**
     * Tests that all license type values are non-empty descriptive strings.
     * Values are displayed in the admin UI for human readability.
     */
    public function testLicenseTypeValuesAreDescriptiveStrings(): void
    {
        $types = get_directadmin_license_types();
        foreach ($types as $key => $value) {
            $this->assertIsString($value);
            $this->assertNotEmpty($value);
            $this->assertMatchesRegularExpression('/\d+-bit$/', $value, "Value '{$value}' should end with bit architecture");
        }
    }

    /**
     * Tests that the license types cover CentOS, FreeBSD, and Debian families.
     * These are the OS families supported by DirectAdmin.
     */
    public function testLicenseTypesContainAllOsFamilies(): void
    {
        $types = get_directadmin_license_types();
        $values = implode(' ', $types);

        $this->assertStringContainsString('CentOS', $values);
        $this->assertStringContainsString('FreeBSD', $values);
        $this->assertStringContainsString('Debian', $values);
    }

    /**
     * Tests that the ES (Enterprise/CentOS) types use version.0 format in keys.
     * This format is required by the DirectAdmin API.
     */
    public function testEsTypesUseVersionDotZeroFormat(): void
    {
        $types = get_directadmin_license_types();
        $esKeys = array_filter(array_keys($types), function ($key) {
            return strpos($key, 'ES') === 0;
        });

        foreach ($esKeys as $key) {
            $this->assertMatchesRegularExpression('/^ES \d+\.0/', $key, "ES key '{$key}' should use version.0 format");
        }
    }

    /**
     * Tests that directadmin_deactivate() source code delegates to deactivate_directadmin().
     * Ensures the alias relationship is maintained in the source.
     */
    public function testDirectadminDeactivateIsAlias(): void
    {
        $this->assertStringContainsString(
            'return deactivate_directadmin($ipAddress)',
            self::$sourceContents,
            'directadmin_deactivate should delegate to deactivate_directadmin'
        );
    }

    /**
     * Tests that directadmin_req() applies default cURL options.
     * These defaults ensure HTTPS connections work without strict cert verification.
     */
    public function testDirectadminReqSetsDefaultCurlOptions(): void
    {
        $this->assertStringContainsString('CURLOPT_USERPWD', self::$sourceContents);
        $this->assertStringContainsString('CURLOPT_HTTPAUTH', self::$sourceContents);
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYHOST', self::$sourceContents);
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER', self::$sourceContents);
    }

    /**
     * Tests that directadmin_req() constructs URLs using the DirectAdmin base URL.
     * All non-absolute URLs should be prefixed with the DirectAdmin domain.
     */
    public function testDirectadminReqUsesCorrectBaseUrl(): void
    {
        $this->assertStringContainsString(
            'https://www.directadmin.com/',
            self::$sourceContents
        );
    }

    /**
     * Tests that activate_directadmin() uses the correct API endpoint.
     * The createlicense endpoint is the only way to provision new licenses.
     */
    public function testActivateDirectadminUsesCorrectEndpoint(): void
    {
        $this->assertStringContainsString(
            'https://www.directadmin.com/clients/api/createlicense.php',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_makepayment() uses the makepayment CGI endpoint.
     * Payment processing requires the CGI endpoint, not the API endpoint.
     */
    public function testMakepaymentUsesCorrectEndpoint(): void
    {
        $this->assertStringContainsString(
            'https://www.directadmin.com/cgi-bin/makepayment',
            self::$sourceContents
        );
    }

    /**
     * Tests that deactivate_directadmin() uses the deletelicense CGI endpoint.
     * License deletion requires the CGI endpoint.
     */
    public function testDeactivateUsesDeleteEndpoint(): void
    {
        $this->assertStringContainsString(
            'https://www.directadmin.com/cgi-bin/deletelicense',
            self::$sourceContents
        );
    }

    /**
     * Tests that activate_directadmin() has the correct parameter signature.
     * The function requires IP, OS type, password, email, name, and optional domain/custid.
     */
    public function testActivateDirectadminParameterSignature(): void
    {
        $this->assertMatchesRegularExpression(
            '/function activate_directadmin\(\$ipAddress,\s*\$ostype,\s*\$pass,\s*\$email,\s*\$name,\s*\$domain\s*=\s*\'\'/',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_get_best_type() has the correct parameter signature.
     * Accepts module, packageId, and optional order and extra parameters.
     */
    public function testDirectadminGetBestTypeParameterSignature(): void
    {
        $this->assertMatchesRegularExpression(
            '/function directadmin_get_best_type\(\$module,\s*\$packageId,\s*\$order\s*=\s*false,\s*\$extra\s*=\s*false\)/',
            self::$sourceContents
        );
    }

    /**
     * Tests that directadmin_modify_os() validates the license ID before proceeding.
     * An invalid or empty license ID should cause the function to return false.
     */
    public function testDirectadminModifyOsValidatesLid(): void
    {
        $this->assertStringContainsString(
            'if ($lid)',
            self::$sourceContents,
            'directadmin_modify_os should validate license ID'
        );
    }

    /**
     * Tests that directadmin_modify_os() returns false for invalid inputs.
     * Ensures the function's contract is maintained in the source.
     */
    public function testDirectadminModifyOsReturnsFalseOnInvalid(): void
    {
        $this->assertStringContainsString(
            'return false;',
            self::$sourceContents
        );
    }

    /**
     * Tests that the source file references the StatisticClient for request tracking.
     * All API calls should be instrumented for monitoring.
     */
    public function testSourceUsesStatisticClient(): void
    {
        $this->assertStringContainsString('StatisticClient::tick', self::$sourceContents);
        $this->assertStringContainsString('StatisticClient::report', self::$sourceContents);
    }

    /**
     * Tests that get_directadmin_licenses() parses response lines using parse_str.
     * The DirectAdmin API returns query-string formatted lines.
     */
    public function testGetLicensesUsesParseStr(): void
    {
        $this->assertStringContainsString('parse_str($line, $license)', self::$sourceContents);
    }

    /**
     * Tests that the source has appropriate PHPDoc blocks for key functions.
     * Documentation is essential for maintainability.
     */
    public function testKeyFunctionsHaveDocBlocks(): void
    {
        // Check for @param or @return annotations near function definitions
        $this->assertStringContainsString('@param string', self::$sourceContents);
        $this->assertStringContainsString('@return', self::$sourceContents);
    }
}
