<?php

namespace Detain\MyAdminDirectadmin;

//use Detain\Directadmin\Directadmin;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminDirectadmin
 */
class Plugin
{
	public static $name = 'DirectAdmin Licensing';
	public static $description = 'Allows selling of DirectAdmin Server and VPS License Types.  More info at https://www.directadmin.com/';
	public static $help = '';
	public static $module = 'licenses';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
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
	public static function getActivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('DIRECTADMIN')) {
			myadmin_log(self::$module, 'info', 'Directadmin Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
			$freeDaTypes = run_event('get_free_da_service_types', true, 'licenses');
			if (in_array($serviceClass->getType(), array_keys($freeDaTypes))) {
				function_requirements('activate_free_license');
				$response = activate_free_license($serviceClass->getIp(), $serviceClass->getType(), $event['email'], $serviceClass->getHostname());
			} else {
				function_requirements('directadmin_get_best_type');
				function_requirements('activate_directadmin');
				$response = activate_directadmin($serviceClass->getIp(), directadmin_get_best_type(self::$module, $serviceClass->getType()), $event['email'], $event['email'], self::$module.$serviceClass->getId(), '');
			}
			$serviceClass
				->setKey($response)
				->save();
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('DIRECTADMIN')) {
			myadmin_log(self::$module, 'info', 'Directadmin Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
			function_requirements('deactivate_directadmin');
			$event['success'] = deactivate_directadmin($serviceClass->getIp());
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event)
	{
		if ($event['category'] == get_service_define('DIRECTADMIN')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$directadmin = new \Directadmin(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
			$result = $directadmin->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Directadmin editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
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
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_directadmin', '/images/myadmin/to-do.png', _('ReUsable Directadmin Licenses'));
			$menu->add_link(self::$module, 'choice=none.directadmin_list', '/images/myadmin/to-do.png', _('Directadmin Licenses Breakdown'));
			$menu->add_link(self::$module.'api', 'choice=none.directadmin_licenses_list', '/images/whm/createacct.gif', _('List all Directadmin Licenses'));
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_requirement('get_directadmin_license_types', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_page_requirement('directadmin_get_best_type', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_page_requirement('directadmin_req', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('get_directadmin_licenses', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('get_directadmin_license', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('get_directadmin_license_by_ip', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_page_requirement('directadmin_ip_to_lid', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('activate_directadmin', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('deactivate_directadmin', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_requirement('directadmin_deactivate', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_page_requirement('directadmin_makepayment', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
		$loader->add_page_requirement('activate_free_license', '/../vendor/detain/myadmin-directadmin-licensing/src/directadmin.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, _('DirectAdmin'), 'directadmin_username', _('Directadmin Username'), _('Directadmin Username'), $settings->get_setting('DIRECTADMIN_USERNAME'));
		$settings->add_text_setting(self::$module, _('DirectAdmin'), 'directadmin_password', _('Directadmin Password'), _('Directadmin Password'), $settings->get_setting('DIRECTADMIN_PASSWORD'));
		$settings->add_dropdown_setting(self::$module, _('DirectAdmin'), 'outofstock_licenses_directadmin', _('Out Of Stock DirectAdmin Licenses'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_LICENSES_DIRECTADMIN'), ['0', '1'], ['No', 'Yes']);
	}
}
