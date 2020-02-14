<?php

namespace IQnection\BigCommerceApp\View;

use SilverStripe\View\TemplateGlobalProvider;
use IQnection\BigCommerceApp\App\Main;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;

class TemplateProvider implements TemplateGlobalProvider
{
	public static function get_template_global_variables()
	{
		return [
			'Dashboard' => 'getDashboardApp'
		];
	}
	
	public static $_apps = [];
	public static function getDashboardApp($appName = null)
	{
		$appClass = Main::class;
    	$apps = $appClass::Config()->get('apps');
		$controller = Controller::curr();
		if ($appName = $appName ? $appName : get_class($controller))
		{
			if (isset($apps[$appName]))
			{
				$appClass = $apps[$appName];
			}
			elseif (in_array($appName,$apps))
			{
				foreach($apps as $_appName => $_appClass)
				{
					if ($_appClass == $appName)
					{
						$appClass = $_appClass;
						break;
					}
				}
			}
		}
		if (!isset(self::$_apps[$appClass]))
		{
			self::$_apps[$appClass] = Injector::inst()->get($appClass);
			self::$_apps[$appClass]->setRequest($controller->getRequest());
		}
		return self::$_apps[$appClass];
	}
}