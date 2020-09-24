<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;
use IQnection\BigCommerceApp\Model\Category;
use IQnection\BigCommerceApp\Cron\BackgroundJob;

class Categories extends Main
{
	private static $managed_class = Category::class;
	private static $url_segment = '_bc/categories';
	private static $allowed_actions = [
		'view',
		'sync',
		'pull',
		
	];
	
	private static $nav_links = [
		'Categories' => [
			'path' => '',
			'icon' => 'th-large'
		]
	];
	
	private static $theme_packages = [
		'forms',
	];
	
	public function index()
	{
		return $this->Customise([
			'Categories' => Category::get()
		]);
	}

	public function pull()
	{
		if ( (!$record = $this->currentRecord()) || (!$record->Exists()) )
		{
			BackgroundJob::CreateJob(\IQnection\BigCommerceApp\Cron\SyncCategories::class, 'syncCategories');
			$this->addAlert('All categories scheduled to sync');
		}
		else
		{
			try {
				$record->SyncFromApi();
				$this->addAlert('Data Updated');
			} catch (\Exception $e) {
				$this->addAlert($e->getMessage(),'danger');
			}
		}
		return $this->redirectBack();
	}	
}









