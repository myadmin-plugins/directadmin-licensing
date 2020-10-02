<?php

/**
* DirectAdmin Related Functionality
* @author Joe Huss <detain@interserver.net>
* @copyright 2019
* @package MyAdmin
* @category Licenses
*/

function get_directadmin_license_types()
{
	return [
		'ES 5.0'			=>		'CentOS 5 32-bit',
		'ES 5.0 64'			=>		'CentOS 5 64-bit',
		'ES 6.0'			=>		'CentOS 6 32-bit',
		'ES 6.0 64'			=>		'CentOS 6 64-bit',
		'ES 7.0 64'			=>		'CentOS 7 64-bit',
		'FreeBSD 8.0 64'	=>		'FreeBSD 8.x 64-bit',
		'FreeBSD 9.1 32'	=>		'FreeBSD 9.x 32-bit',
		'FreeBSD 9.0 64'	=>		'FreeBSD 9.x 64-bit',
		'Debian 5'			=>		'Debian 5.0 32-bit',
		'Debian 5 64'		=>		'Debian 5.0 64-bit',
		'Debian 6'			=>		'Debian 6.0 32-bit',
		'Debian 6 64'		=>		'Debian 6.0 64-bit',
		'Debian 7'			=>		'Debian 7.0 32-bit',
		'Debian 7 64'		=>		'Debian 7.0 64-bit',
		'Debian 8 64'		=>		'Debian 8.0 64-bit'
	];
}

/**
* @param string $module
* @param $packageId
* @param bool $order
* @param bool|array $extra
* @return bool|string
*/
function directadmin_get_best_type($module, $packageId, $order = false, $extra = false)
{
	$types = get_directadmin_license_types();
	$db = get_module_db($module);
	$found = false;
	$parts = [];
	$settings = \get_module_settings($module);
	$db->query("select * from services where services_id={$packageId}");
	if ($db->next_record(MYSQL_ASSOC)) {
		if ($module == 'licenses') {
			return $db->Record['services_field1'];
		}
		$service = $db->Record;
		if ($db->Record['services_field1'] != 'slice') {
			$parts = explode(' ', $db->Record['services_name']);
			$parts[3] = trim(str_replace(['-', 'bit'], ['', ''], $parts[3]));
		}
		if ($extra === false) {
			$extra = ['os' => '', 'version' => ''];
		}
	}
	if (!isset($order[$settings['PREFIX'].'_os']) || $order[$settings['PREFIX'].'_os'] == '') {
		if (in_array($service['services_type'], [get_service_define('KVM_LINUX'), get_service_define('CLOUD_KVM_LINUX')])) {
			$order[$settings['PREFIX'].'_os'] = 'centos5';
		} elseif (in_array($service['services_type'], [get_service_define('OPENVZ'), get_service_define('SSD_OPENVZ')])) {
			$db->query("select * from {$settings['PREFIX']}_masters where {$settings['PREFIX']}_id={$order[$settings['PREFIX'].'_server']}");
			$db->next_record(MYSQL_ASSOC);
			if ($db->Record[$settings['PREFIX'].'_bits'] == 32) {
				$order[$settings['PREFIX'].'_os'] = 'centos-6-x86.tar.gz';
			} else {
				$order[$settings['PREFIX'].'_os'] = 'centos-6-x86_64.tar.gz';
			}
		}
	}
	if (isset($order[$settings['PREFIX'].'_os'])) {
		$db->query("select * from vps_templates where template_file='".$db->real_escape($order[$settings['PREFIX'].'_os'])."' limit 1", __LINE__, __FILE__);
		if ($db->num_rows() > 0) {
			$db->next_record(MYSQL_ASSOC);
			$found = true;
			$parts = [$db->Record['template_os'], $db->Record['template_version'], $db->Record['template_bits']];
		}
	}
	if (in_array(strtolower($parts[2]), ['i386', 'i586', 'x86'])) {
		$parts[2] = 32;
	} elseif (in_array(strtolower($parts[2]), ['amd64', 'x86-64'])) {
		$parts[2] = 64;
	}
	if (in_array(strtolower($db->Record['template_os']), ['debian'])) {
		$parts[0] = 'Debian';
	} elseif (in_array(strtolower($db->Record['template_os']), ['ubuntu'])) {
		$parts[0] = 'Debian';
		$parts[1] = '8';
	} elseif (in_array(strtolower($db->Record['template_os']), ['freebsd', 'openbsd'])) {
		$parts[0] = 'FreeBSD';
	} elseif (in_array(strtolower($db->Record['template_os']), ['centos', 'fedora', 'rhel', 'redhat'])) {
		$parts[0] = 'ES';
	} else {
		$parts[0] = $db->Record['template_os'];
	}
	if (strtolower($parts[0]) == 'FreeBSD') {
		if ($parts[3] == 32) {
			$parts[2] = '9.1';
		} elseif (mb_substr($parts[2], 0, 1) == 8) {
			$parts[2] = '8.0';
		} elseif (mb_substr($parts[2], 0, 1) == 9) {
			$parts[2] = '9.0';
		}
	} elseif (!isset($parts[3]) || $parts[3] == 32) {
		$parts[3] = '';
	}
	if ($parts[0] == 'ES') {
		$parts[1] = mb_substr($parts[1], 0, 1).'.0';
	} else {
		$parts[1] = mb_substr($parts[1], 0, 1);
	}
	$daType = trim("{$parts[0]} {$parts[1]} {$parts[2]}");
	if (isset($types[$daType])) {
		myadmin_log('licenses', 'info', "Matched DA Type for $types[$daType] to {$daType}", __LINE__, __FILE__);
		return $daType;
	} else {
		myadmin_log('licenses', 'info', "Couldn't find matching da type from os {$daType} fakkubg back go ES 9.0 64", __LINE__, __FILE__);
		return "ES 9.0 64";
	}
	return false;
}

/**
* @param string        $page
* @param string        $post
* @param bool|string[] $options
* @return string
*/
function directadmin_req($page, $post = '', $options = false)
{
	require_once __DIR__.'/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';

	if ($options === false) {
		$options = [];
	}
	$defaultOptions = [
		CURLOPT_USERPWD => DIRECTADMIN_USERNAME.':'.DIRECTADMIN_PASSWORD,
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false
	];
	foreach ($defaultOptions as $key => $value) {
		if (!isset($options[$key])) {
			$options[$key] = $value;
		}
	}
	if (!is_url($page)) {
		if (mb_strpos($page, '.php') === false) {
			$page .= '.php';
		}
		if (mb_strpos($page, '/') === false) {
			$page = "clients/api/{$page}";
		} elseif (mb_strpos($page, 'api/') === false) {
			$page = "api/{$page}";
		}
		if (mb_strpos($page, 'clients/') === false) {
			$page == "clients/{$page}";
		}
		if (!is_url($page)) {
			$page = "https://www.directadmin.com/{$page}";
		}
	}
	$call = basename(parse_url($page)['path'], '.php');
	\StatisticClient::tick('DirectAdmin', $call);
	$response = getcurlpage($page, $post, $options);
	if ($response === false) {
		\StatisticClient::report('DirectAdmin', $call, false, 1, 'Curl Error', STATISTICS_SERVER);
	} else {
		\StatisticClient::report('DirectAdmin', $call, true, 0, '', STATISTICS_SERVER);
	}
	return trim($response);
}

/**
* @return array
*/
function get_directadmin_licenses()
{
	$response = directadmin_req('list');
	$licenses = [];
	if (trim($response) == '') {
		return $licenses;
	}
	$lines = explode("\n", trim($response));
	$linesValues = array_values($lines);
	foreach ($linesValues as $line) {
		parse_str($line, $license);
		$licenses[$license['lid']] = $license;
	}
	return $licenses;
}       
/**
* @param $lid
* @return string
*/
function get_directadmin_license($lid)
{
	$response = directadmin_req('license', ['lid' => $lid]);
	_debug_array($response);
	return $response;
}

/**
* @param $ipAddress
* @return bool|mixed
*/
function get_directadmin_license_by_ip($ipAddress)
{
	$licenses = get_directadmin_licenses();
	$licensesValues = array_values($licenses);
	foreach ($licensesValues as $license) {
		if ($license['ip'] == $ipAddress) {
			return $license;
		}
	}
	return false;
}

/**
* @param $ipAddress
* @return bool
*/
function directadmin_ip_to_lid($ipAddress)
{
	$license = get_directadmin_license_by_ip($ipAddress);
	if ($license === false) {
		return false;
	} else {
		return $license['lid'];
	}
}

/**
* activate_directadmin()
*
* @param $ipAddress
* @param boolean|string $ostype
* @param $pass
* @param $email
* @param string $name
* @param string $domain
* @param false|int $custid optional customer id or null for none
* @return string license id
*/
function activate_directadmin($ipAddress, $ostype, $pass, $email, $name, $domain = '', $custid = null)
{
	myadmin_log('licenses', 'info', "Called activate_directadmin($ipAddress, $ostype, $pass, $email, $name, $domain)", __LINE__, __FILE__);
	$settings = \get_module_settings('licenses');
	$license = get_directadmin_license_by_ip($ipAddress);
	if ($license === false) {
		$options = [
			CURLOPT_REFERER => 'https://www.directadmin.com/clients/createlicense.php'
		];
		if (strpos($ostype, ',') !== false) {
			list($pid, $os) = explode(',', $ostype);
			$ostype = $os;
		} else {
			$pid = 2712;
		}
		$post = [
			'uid' =>  DIRECTADMIN_USERNAME,
			'id' => DIRECTADMIN_USERNAME,
			'password' => DIRECTADMIN_PASSWORD,
			'api' => 1,
			'name' => $name,
			'pid' => $pid,
			'os' => $ostype,
			'payment' => 'balance',
			'ip' => $ipAddress,
			'pass1' => $pass,
			'pass2' => $pass,
			'username' => 'admin',
			'email' => $email,
			'admin_pass1' => $pass,
			'admin_pass2' => $pass,
			'ns1' => 'dns4.interserver.net',
			'ns2' => 'dns5.interserver.net',
			'ns_on_server' => 'yes',
			'ns1ip' => '66.45.228.78',
			'ns2ip' => '66.45.228.3'
		];
		if ($domain != '') {
			$post['domain'] = $domain;
		} else {
			$post['domain'] = $post['ip'];
		}
		$url = 'https://www.directadmin.com/cgi-bin/createlicense';
		$response = directadmin_req($url, $post, $options);
		request_log('licenses', $GLOBALS['tf']->session->account_id, __FUNCTION__, 'directadmin', 'createlicense', $post, $response);
		myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
		$matches = preg_split('/error=0&text=License Created&lid=/', $response);
		if (!empty($matches) && $matches[1] != '') {
			$lid = urldecode($matches[1]);
			$response = directadmin_makepayment($lid);
			request_log('licenses', $GLOBALS['tf']->session->account_id, __FUNCTION__, 'directadmin', 'makepayment', $lid, $response);
			myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
		}
		$GLOBALS['tf']->history->add($settings['TABLE'], 'add_directadmin', 'ip', $ipAddress, $custid);
		return $lid;
	}
	return $license['lid'];
}

/**
* deactivate_directadmin()
* @param mixed $ipAddress
* @return string|null
*/
function deactivate_directadmin($ipAddress)
{
	$module = 'licenses';
	$response = get_directadmin_licenses();
	foreach ($response as $idx => $data) {
		if ($data['ip'] == $ipAddress) {
			$license = $data;
		}
	}
	if (!isset($license)) {    
		$license = get_directadmin_license_by_ip($ipAddress);
	}
	if ($license['active'] == 'Y') {
		$url = 'https://www.directadmin.com/cgi-bin/deletelicense';
		$post = [
			'uid' => DIRECTADMIN_USERNAME,
			'password' => DIRECTADMIN_PASSWORD,
			'api' => 1,
			'lid' => $license['lid']
		];
		$options = [
			//CURLOPT_REFERER => 'https://www.directadmin.com/clients/license.php',
			CURLOPT_REFERER => 'https://www.directadmin.com/clients/license.php?lid='.$license['lid']
		];
		$response = directadmin_req($url, $post, $options);
		myadmin_log('licenses', 'info', $response, __LINE__, __FILE__);
		request_log($module, $GLOBALS['tf']->session->account_id, __FUNCTION__, 'directadmin', 'deactivateLicense', $post, $response);
		$deActdLicense = get_directadmin_license_by_ip($ipAddress);
		$bodyRows = [];
		if ($deActdLicense['active'] == 'Y') {
			$bodyRows[] = 'DirectAdmin license IP: '.$ipAddress.' unable to cancel.';
			$bodyRows[] = 'Deactivation Response: .'.json_encode($response);
			$subject = 'License Deactivation Issue IP: '.$ipAddress;
			$smartyE = new TFSmarty;
			$smartyE->assign('h1', 'License Deactivation');
			$smartyE->assign('body_rows', $bodyRows);
			$msg = $smartyE->fetch('email/client/client_email.tpl');
			(new \MyAdmin\Mail())->adminMail($subject, $msg, ADMIN_EMAIL, 'client/client_email.tpl');
		}
		return true;
	}
}

/**
* @param $ipAddress
* @return null|string
*/
function directadmin_deactivate($ipAddress)
{
	return deactivate_directadmin($ipAddress);
}

/**
* @param string $lid
* @return string
*/
function directadmin_makepayment($lid)
{
	$url = 'https://www.directadmin.com/cgi-bin/makepayment';
	$referrer = 'https://www.directadmin.com/clients/makepayment.php';
	$post = [
		'uid' => DIRECTADMIN_USERNAME,
		'id' => DIRECTADMIN_USERNAME,
		'password' => DIRECTADMIN_PASSWORD,
		'api' => 1,
		'action' => 'pay',
		'lid' => $lid
	];
	$options = [
		CURLOPT_REFERER => $referrer
	];
	$response = directadmin_req($url, $post, $options);
	myadmin_log('licenses', 'info', $response, __LINE__, __FILE__, 'licenses');
	return $response;
}

function directadmin_get_os_list($active = '') {
	$url = 'https://www.directadmin.com/clients/api/os_list.php';
	$post = [
		'uid' => DIRECTADMIN_USERNAME,
		'id' => DIRECTADMIN_USERNAME,
		'password' => DIRECTADMIN_PASSWORD,
		'api' => 1,
	];
	$response = directadmin_req($url, $post);
	myadmin_log('licenses', 'info', $response, __LINE__, __FILE__, 'licenses');
	return $response;
}

function directadmin_get_products() {
	$url = 'https://www.directadmin.com/clients/api/products.php';
	$post = [
		'uid' => DIRECTADMIN_USERNAME,
		'id' => DIRECTADMIN_USERNAME,
		'password' => DIRECTADMIN_PASSWORD,
		'api' => 1,
	];
	$response = directadmin_req($url, $post);
	myadmin_log('licenses', 'info', $response, __LINE__, __FILE__, 'licenses');
	return $response;
}  
