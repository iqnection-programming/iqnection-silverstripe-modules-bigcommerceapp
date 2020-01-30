<?php

namespace IQnection\BigCommerceApp\View;

use SilverStripe\View\TemplateGlobalProvider;
use IQnection\BigCommerceApp\App\Main;
use SilverStripe\Core\Injector\Injector;

class TemplateProvider implements TemplateGlobalProvider
{
	public static function get_template_global_variables()
	{
		return [
			'Dashboard' => 'getDashboardApp'
		];
	}
	
	public static function getDashboardApp($appName = null)
	{
		$appClass = Main::class;
    	$apps = $appClass::Config()->get('apps');
		if (isset($apps[$appName]))
		{
		  $appClass = $apps[$appName];
		}
		return Injector::inst()->get($appClass);
	}
}