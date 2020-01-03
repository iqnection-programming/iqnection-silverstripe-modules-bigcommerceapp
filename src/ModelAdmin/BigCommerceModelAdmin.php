<?php

namespace IQnection\BigCommerceApp\ModelAdmin;

use SilverStripe\Admin\ModelAdmin;

class BigCommerceModelAdmin extends ModelAdmin 
{
	private static $managed_models = [
		\IQnection\BigCommerceApp\Widgets\Widget::class => [
			'title'=>'Widgets'
		],
		\IQnection\BigCommerceApp\Widgets\WidgetTemplate::class => [
			'title'=>'Widget Templates'
		],
		\IQnection\BigCommerceApp\Model\BigCommerceLog::class => [
			'title' => 'API Logs'
		]
	];

//	private static $menu_icon_class = 'font-icon-code';
	private static $menu_title = 'BigCommerce';
	private static $url_segment = 'bc';
	public $showImportForm = false;
}
