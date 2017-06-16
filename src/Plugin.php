<?php

namespace Detain\MyAdminDirectadmin;

//use Detain\Directadmin\Directadmin;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Directadmin Licensing';
	public static $description = 'Allows selling of Directadmin Server and VPS License Types.  More info at https://www.directadmin.com/';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a directadmin license. Allow 10 minutes for activation.';
	public static $module = 'licenses';
	public static $type = 'service';


	public function __construct() {
	}

	public static function Hooks() {
		return [
			'licenses.settings' => [__CLASS__, 'Settings'],
			'licenses.activate' => [__CLASS__, 'Activate'],
			'licenses.deactivate' => [__CLASS__, 'Deactivate'],
			'function.requirements' => [__CLASS__, 'Requirements'],
		];
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_DIRECTADMIN) {
			myadmin_log('licenses', 'info', 'Directadmin Activation', __LINE__, __FILE__);
			function_requirements('directadmin_get_best_type');
			function_requirements('activate_directadmin');
			$result = activate_directadmin($license->get_ip(), directadmin_get_best_type('licenses', $license->get_type()), $event['email'], $event['email'], 'licenses'.$license->get_id(), '');
			$event->stopPropagation();
		}
	}

	public static function Deactivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_DIRECTADMIN) {
			myadmin_log('licenses', 'info', 'Directadmin Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_directadmin');
			deactivate_directadmin($license->get_ip());
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_DIRECTADMIN) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$directadmin = new \Directadmin(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $directadmin->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Directadmin editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_directadmin', 'icons/database_warning_48.png', 'ReUsable Directadmin Licenses');
			$menu->add_link($module, 'choice=none.directadmin_list', 'icons/database_warning_48.png', 'Directadmin Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.directadmin_licenses_list', 'whm/createacct.gif', 'List all Directadmin Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
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

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'DirectAdmin', 'directadmin_username', 'Directadmin Username:', 'Directadmin Username', $settings->get_setting('DIRECTADMIN_USERNAME'));
		$settings->add_text_setting('licenses', 'DirectAdmin', 'directadmin_password', 'Directadmin Password:', 'Directadmin Password', $settings->get_setting('DIRECTADMIN_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'DirectAdmin', 'outofstock_licenses_directadmin', 'Out Of Stock DirectAdmin Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_DIRECTADMIN'), array('0', '1'), array('No', 'Yes', ));
	}

}
