<?php

namespace Detain\MyAdminDirectadmin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * activate_directadmin() and deactivate_directadmin() write their API posts to
 * request_log and a call line to the log table; neither may carry our account
 * password or the new license's admin password.
 */
class DirectadminLogRedactionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/src/directadmin.inc.php';
    }

    public function testLoggablePostMasksEveryPasswordField(): void
    {
        $post = [
            'uid' => 'reseller',
            'id' => 'reseller',
            'password' => 'AccountPass1',
            'api' => 1,
            'ip' => '192.0.2.9',
            'pass1' => 'LicensePass1',
            'pass2' => 'LicensePass1',
            'admin_pass1' => 'LicensePass1',
            'admin_pass2' => 'LicensePass1',
            'email' => 'user@example.com',
        ];
        $out = directadmin_loggable_post($post);
        foreach (['password', 'pass1', 'pass2', 'admin_pass1', 'admin_pass2'] as $key) {
            $this->assertSame('[redacted]', $out[$key], $key);
        }
        foreach (['uid', 'id', 'api', 'ip', 'email'] as $key) {
            $this->assertSame($post[$key], $out[$key], $key);
        }
        $encoded = json_encode($out);
        $this->assertStringNotContainsString('AccountPass1', $encoded);
        $this->assertStringNotContainsString('LicensePass1', $encoded);
        $this->assertSame('AccountPass1', $post['password']);
    }

    public function testLoggablePostPassesNonArraysThrough(): void
    {
        $this->assertSame('', directadmin_loggable_post(''));
    }

    public function testNoLogCallCarriesTheRawPostOrPassword(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__) . '/src/directadmin.inc.php');
        $this->assertStringNotContainsString('{$pass}', $source);
        preg_match_all('/^\s*request_log\(.*$/m', $source, $lines);
        $this->assertNotEmpty($lines[0]);
        foreach ($lines[0] as $line) {
            $this->assertStringNotContainsString(', $post,', $line);
        }
    }
}
