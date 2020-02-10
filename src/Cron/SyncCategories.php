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

class SyncCategories extends Sync
{
	protected $title = 'Sync BigCommerce Categories';
	protected $description = 'Pulls categories from BigCommerce';
	private static $segment = 'sync-bc-categories';
	
	public function run($request)
	{
		$this->syncCategories();
	}
	
	public function syncCategories()
	{
		$this->message('Syncing Records from BigCommerce');
		$bcCategoryEntity = Category::singleton()->Entity();
		$this->message('Retrieving BigCommerce Categories');
		$bcCategories = $bcCategoryEntity::getCategories(true);
		$this->message($bcCategories->Count().' BigCommerce Categories Found');
		$this->message('Retrieving Database Categories');
		$allDbCategoryIDs = Category::get()->Column('ID');
		$this->message(count($allDbCategoryIDs).' Database Categories Found');
		$syncedIDs = [];
		$updated = 0;
		$created = 0;
		foreach($bcCategories as $bcCategory)
		{
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
			$this->message($status.' Category: ['.$bcCategory->id.'] '.$bcCategory->name);
			$category->invokeWithExtensions('loadFromApi',$bcCategory);
			//$category->loadFromApi($bcCategory);
			$category->write();
			$syncedIDs[] = $category->ID;
			
			usleep(500000);
		}
		
		// remove left over categories
		$removed = 0;
		$removeCategories = Category::get()->Exclude('ID',$syncedIDs);
		$this->message($removeCategories->Count().' Categories to remove');
		foreach($removeCategories as $category)
		{
			$this->message('Removing: '.$category->Title);
			$category->delete();
			$removed++;
		}
		
		$this->message($updated+$created+$removed.' Categories Synced');
		$this->message($updated.' Categories Updated');
		$this->message($created.' Categories Created');
		$this->message($removed.' Categories Removed');
	}
}