<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_directadmin define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Directadmin Licensing',
	'description' => 'Allows selling of Directadmin Server and VPS License Types.  More info at https://www.directadmin.com/',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a directadmin license. Allow 10 minutes for activation.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-directadmin-licensing',
	'repo' => 'https://github.com/detain/myadmin-directadmin-licensing',
	'version' => '1.0.0',
	'type' => 'licenses',
	'hooks' => [
		'licenses.settings' => ['Detain\MyAdminDirectadmin\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminDirectadmin\Plugin', 'Activate'],
		'licenses.deactivate' => ['Detain\MyAdminDirectadmin\Plugin', 'Deactivate'],
		'function.requirements' => ['Detain\MyAdminDirectadmin\Plugin', 'Requirements'],
		/* 'licenses.change_ip' => ['Detain\MyAdminDirectadmin\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminDirectadmin\Plugin', 'Menu'] */
	],
];
