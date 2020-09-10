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
		$this->_syncCategories($request);
	}
	
	public function _syncCategories($request)
	{
		$this->message('Syncing Records from BigCommerce');
		$bcCategoryEntity = Category::singleton()->Entity();
		$this->message('Retrieving Database Categories');
		$allDbCategoryIDs = Category::get()->Column('ID');
		$this->message(count($allDbCategoryIDs).' Database Categories Found');
		sleep(2);
		$syncedIDs = [];
		$totalBcCats = 0;
		$updated = 0;
		$created = 0;
		$removed = 0;
		if (!$page = $request->getVar('page'))
		{
			$page = 1;
		}
		$count = count($allDbCategoryIDs);
		$params = ['page' => $page, 'limit' => 100];
		$this->message('Retrieving BigCommerce Categories | Page: '.$params['page']);
		$bcCategories = $bcCategoryEntity::getAll(true,$params);
		while($bcCategories->Count())
		{
			$currentItCount = $bcCategories->Count();
			$this->message('Page '.$params['page'].' Categories Found: '.$currentItCount);
			foreach($bcCategories as $bcCategory)
			{
				$totalBcCats++;
				$count--;
				$status = 'Updating';
				$foundCategories = Category::get()->Filter('BigID', $bcCategory->id);
				$category = false;
				$foundCategoriesCount = $foundCategories->Count();
				$keep = true;
				if ($foundCategoriesCount)
				{
					foreach($foundCategories as $foundCategory)
					{
						if ($category)
						{
							$count--;
							$syncedIDs[] = $foundCategory->ID;
							$this->message('Duplicate Found - Removing ID: '.$foundCategory->ID);
							$foundCategory->delete();
							$removed++;
							usleep(500000);
							continue;
						}
						$category = $foundCategory;
					}
				}
				else
				{
					$filter = ['Title' => $bcCategory->name, 'BigID' => null];
					if ($bcCategory->parent_id)
					{
						if (!$parentCategory = Category::get()->Find('BigID',$bcCategory->parent_id))
						{
							$this->message('SKIPPING CATEGORY, PARENT NOT YET CREATED');
							continue;
						}
						$filter['ParentID'] = $parentCategory->ID;
					}
					else
					{
						$filter['ParentID'] = 0;
					}
					if (!$category = Category::get()->Filter($filter)->First())
					{
						$this->message($count.' - Category Does Not Exist: [BigID:'.$bcCategory->id.'] '.$bcCategory->name);
						$created++;
						$status = 'Creating';
						// create the new category
						$category = Category::create();
						$category->BigID = $bcCategory->id;
					}
					else
					{
						$this->message('Syncing BIG ID');
					}
				}
				$this->message($count.' - '.$status.' Category: [BigID:'.$bcCategory->id.'|ID:'.$category->ID.'] '.$bcCategory->name);
				$lastEdited = $category->LastEdited;
				$category->invokeWithExtensions('loadApiData',$bcCategory);
				$updated++;
				$category->write();
				$syncedIDs[] = $category->ID;
				$currentItCount--;
				if ($category->LastEdited == $lastEdited)
				{
					$this->message('No changes');
				}
				
			}
			$params['page']++;
			$this->message('Retrieving BigCommerce Categories | Page: '.$params['page']);
			$bcCategories = $bcCategoryEntity::getAll(false,$params);
			$this->message('Page '.$params['page'].' Categories Found: '.$bcCategories->Count());
			sleep(1);
		}
		if (!$request->getVar('page'))
		{
			sleep(2);
			// remove left over categories
			$removeCategories = Category::get()->Exclude('ID',$syncedIDs);
			$this->message($removeCategories->Count().' Categories to remove');
			$count = $removeCategories->Count();
			foreach($removeCategories as $category)
			{
				$count--;
				$this->message($count.' - Removing: [BigID:'.$category->BigID.'|ID:'.$category->ID.']'.$category->Title);
				$category->delete();
				$removed++;
			}
		}
		
		$notification = [
			'Total BigCommerce Categories Pulled: '.$totalBcCats,
			$updated+$created+$removed.' Categories Synced',
			$updated.' Categories Updated',
			$created.' Categories Created',
			$removed.' Categories Removed'
		];
		Notification::NotifyAll(implode("<br />", $notification));
		$this->message($notification);
	}
}