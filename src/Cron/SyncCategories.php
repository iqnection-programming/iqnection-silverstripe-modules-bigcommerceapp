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
use IQnection\BigCommerceApp\Model\Notification;

class SyncCategories extends Sync
{
	protected $title = 'Sync BigCommerce Categories';
	protected $description = 'Pulls categories from BigCommerce';
	private static $segment = 'sync-bc-categories';
	
	public function run($request = null)
	{
		$this->checkCli();
		$this->_syncCategories();
	}
	
	public function _syncCategories()
	{
		$this->message('Syncing Records from BigCommerce');
		$bcCategoryEntity = Category::singleton()->Entity();
		$this->message('Retrieving BigCommerce Categories');
		$bcCategories = $bcCategoryEntity::getAll(true);
		$this->message($bcCategories->Count().' BigCommerce Categories Found');
		$this->message('Retrieving Database Categories');
		$allDbCategoryIDs = Category::get()->Column('ID');
		$this->message(count($allDbCategoryIDs).' Database Categories Found');
		sleep(2);
		$syncedIDs = [];
		$updated = 0;
		$created = 0;
		$count = count($allDbCategoryIDs);
		foreach($bcCategories as $bcCategory)
		{
			$count--;
			$status = 'Updating';
			if (!$category = Category::get()->Find('BigID', $bcCategory->id))
			{
				$created++;
				$status = 'Creating';
				// create the new category
				$category = Category::create();
				$category->BigID = $bcCategory->id;
			}
			else
			{
				$updated++;
			}
			$this->message($count.' - '.$status.' Category: ['.$bcCategory->id.'] '.$bcCategory->name);
			$category->invokeWithExtensions('loadApiData',$bcCategory);
			//$category->loadApiData($bcCategory);
			$category->write();
			$syncedIDs[] = $category->ID;
			
		}
		sleep(2);
		// remove left over categories
		$removed = 0;
		$removeCategories = Category::get()->Exclude('ID',$syncedIDs);
		$this->message($removeCategories->Count().' Categories to remove');
		$count = $removeCategories->Count();
		foreach($removeCategories as $category)
		{
			$count--;
			$this->message($count.' - Removing: '.$category->Title);
			$category->delete();
			$removed++;
		}
		
		$notification = [
			$updated+$created+$removed.' Categories Synced',
			$updated.' Categories Updated',
			$created.' Categories Created',
			$removed.' Categories Removed'
		];
		Notification::NotifyAll(implode("<br />", $notification));
		$this->message($notification);
	}
}