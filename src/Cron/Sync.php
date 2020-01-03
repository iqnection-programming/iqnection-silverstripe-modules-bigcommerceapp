<?php

namespace IQnection\BigCommerceApp\Cron;

use SilverStripe\Dev\BuildTask;
use IQnection\BigCommerceApp\Widgets\WidgetTemplate;
use IQnection\BigCommerceApp\Widgets\Widget;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;
use SilverStripe\Control\Director;

class Sync extends BuildTask
{
	protected $title = 'Sync With BigCommerce';
	protected $description = 'Looks for missing BigCommerce objects and adds them to the database';
	
	public function run($request)
	{
		$this->syncWidgetTemplates();
	}
	
	public function syncWidgetTemplates()
	{
		$this->message('Syncing widget templates');
		$singleton = Injector::inst()->get(WidgetTemplate::class);
		$client = $singleton->ApiClient();
		$created = [];
		try {
			if ($widgetTemplates = $client->getWidgetTemplates(['widget_template_kind' => 'custom'])->getData())
			{
				$this->message(count($widgetTemplates), 'Widget Templates Received');
				foreach($widgetTemplates as $widgetTemplate)
				{
					if (!$dbObject = WidgetTemplate::get()->Find('BigID',$widgetTemplate->getUuid()))
					{
						$dbObject = WidgetTemplate::create();
						$dbObject->loadFromApi($widgetTemplate);
						$dbObject->write();
						$this->message($dbObject->BigID,'Creating Widget Template');
					}
				}
			}			
		} catch (\Exception $e) {
			BcLog::info('Error pushing',$e->__toString());
			throw new \SilverStripe\ORM\ValidationException('Error saving: '.$e->getMessage());
		}
		$this->message(count($created),'Widget Templates Created');
	}
	
	public function message($message, $title = null)
	{
		print ((Director::is_cli()) ? "\n" : '<pre>');
		if ($title)
		{
			print (Director::is_cli()) ? $title : '<strong>'.$title.'</strong>';
			print "\n";
		}
		print ((Director::is_cli()) ? null : '<xmp>');
		print_r($message);
		print ((Director::is_cli()) ? "\n" : '</xmp></pre>');
	}
}