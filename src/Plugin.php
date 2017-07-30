<?php

namespace Detain\MyAdminDirectadmin;

//use Detain\Directadmin\Directadmin;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminDirectadmin
 */
class Plugin {

	public static $name = 'DirectAdmin Licensing';
	public static $description = 'Allows selling of DirectAdmin Server and VPS License Types.  More info at https://www.directadmin.com/';
	public static $help = '';
	public static $module = 'licenses';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate'],
			'function.requirements' => [__CLASS__, 'getRequirements']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('DIRECTADMIN')) {
			myadmin_log(self::$module, 'info', 'Directadmin Activation', __LINE__, __FILE__);
			function_requirements('directadmin_get_best_type');
			function_requirements('activate_directadmin');
			activate_directadmin($serviceClass->getIp(), directadmin_get_best_type(self::$module, $serviceClass->getType()), $event['email'], $event['email'], self::$module.$serviceClass->getId(), '');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('DIRECTADMIN')) {
			myadmin_log(self::$module, 'info', 'Directadmin Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_directadmin');
			deactivate_directadmin($serviceClass->getIp());
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == get_service_define('DIRECTADMIN')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$directadmin = new \Directadmin(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $directadmin->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Directadmin editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_directadmin', 'images/icons/database_warning_48.png', 'ReUsable Directadmin Licenses');
			$menu->add_link(self::$module, 'choice=none.directadmin_list', 'images/icons/database_warning_48.png', 'Directadmin Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.directadmin_licenses_list', 'whm/createacct.gif', 'List all Directadmin Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('get_directadmin_license_types', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('directadmin_get_best_type', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('directadmin_req', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('get_directadmin_licenses', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('get_directadmin_license', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('get_directadmin_license_by_ip', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('directadmin_ip_to_lid', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('activate_directadmin', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('deactivate_directadmin', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('directadmin_deactivate', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('directadmin_makepayment', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'DirectAdmin', 'directadmin_username', 'Directadmin Username:', 'Directadmin Username', $settings->get_setting('DIRECTADMIN_USERNAME'));
		$settings->add_text_setting(self::$module, 'DirectAdmin', 'directadmin_password', 'Directadmin Password:', 'Directadmin Password', $settings->get_setting('DIRECTADMIN_PASSWORD'));
		$settings->add_dropdown_setting(self::$module, 'DirectAdmin', 'outofstock_licenses_directadmin', 'Out Of Stock DirectAdmin Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_DIRECTADMIN'), ['0', '1'], ['No', 'Yes']);
	}

}
