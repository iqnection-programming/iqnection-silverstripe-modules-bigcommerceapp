<?php

namespace IQnection\BigCommerceApp\Cron;

use SilverStripe\Dev\BuildTask;
use IQnection\BigCommerceApp\Model\WidgetTemplate;
use IQnection\BigCommerceApp\Model\Widget;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;
use SilverStripe\Control\Director;
use IQnection\BigCommerceApp\Model\Category;
use IQnection\BigCommerceApp\Model\Product;

class Sync extends BuildTask
{
	protected $title = 'Sync With BigCommerce - BASE - CLI ONLY';
	protected $description = 'Base class for creating BigCommerce cron jobs';
	
	protected function checkCli()
	{
		if (!Director::is_cli())
		{
			$this->message('This task should only be run in a cli');
			die();
		}
	}
	
	public function run($request)
	{
		$this->checkCli();
	}
	
	public function message($message, $title = null)
	{
		print ((Director::is_cli()) ? "" : '<pre>');
		if ($title)
		{
			print (Director::is_cli()) ? $title : '<strong>'.$title.'</strong>';
			print "\n";
		}
		if (!Director::is_cli()) { print '<xmp>'; }
		print_r($message);
		print ((Director::is_cli()) ? "\n" : '</xmp></pre>');
	}
}