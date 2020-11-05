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
use SilverStripe\Control\HTTPRequest;

class SyncCategories extends Sync
{
	protected $title = 'Sync BigCommerce Categories';
	protected $description = 'Pulls categories from BigCommerce';
	private static $segment = 'sync-bc-categories';

	protected $_statsLog = [
		'Created' => 0,
		'Updated' => 0,
		'Deleted' => 0,
		'Total' => 0
	];
	protected $_syncedIds = [];
	protected $_count = 0;

	public function run($request = null)
	{
		$this->checkCli();
		$this->_syncAllCategories($request);
	}

	public function _saveCategories($bcCategories, $dbCategories)
	{
		foreach($bcCategories as $bcCategory)
		{
			$this->_count--;
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
						$this->_count--;
						$this->_syncedIds[] = $foundCategory->ID;
						$this->message('Duplicate Found - Removing ID: '.$foundCategory->ID);
						$foundCategory->delete();
						$this->_statsLog['Removed']++;
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
					$this->message($this->_count.' - Category Does Not Exist: [BigID:'.$bcCategory->id.'] '.$bcCategory->name);
					$this->_statsLog['Created']++;
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
			$this->message($this->_count.' - '.$status.' Category: [BigID:'.$bcCategory->id.'|ID:'.$category->ID.'] '.$bcCategory->name);
			$lastEdited = $category->LastEdited;
			$category->invokeWithExtensions('loadApiData',$bcCategory);
			$this->_statsLog['Updated']++;
			$category->write();
			$this->_syncedIds[] = $category->ID;
			$currentItCount--;
			if ($category->LastEdited == $lastEdited)
			{
				$this->message('No changes');
			}

		}
	}

	public function _syncBulkCategories($request)
	{
		// get a bunch of other records to update at once
		$this->message('Getting Background Jobs');
		$backgroundJobs = BackgroundJob::get()->Filter([
			'Status' => BackgroundJob::STATUS_OPEN,
			'Name:PartialMatch' => 'store_category_updated'
		])->limit(100);

		$bigCategoryIds = [];
		foreach($backgroundJobs as $backgroundJob)
		{
			$args = json_decode($backgroundJob->Args, 1);
			$bigCategoryIds[] = $args['BigID'];
		}
		if (!count($bigCategoryIds))
		{
			$this->message('No categories pending updates');
			return;
		}
		$this->_count = count($bigCategoryIds);
		$this->message('Processing '.$this->_count.' Webhooks');
		$this->message('Syncing Bulk Records from BigCommerce');
		$bcCategoryEntity = Category::singleton()->Entity();
		$this->message('Retrieving Database Categories');
		sleep(2);

		$this->_count = count($allDbCategoryIDs);
		$params = ['id:in' => $bigCategoryIds];
		$this->message('Retrieving BigCommerce Categories');
		$bcCategories = $bcCategoryEntity::getAll(true,$params);

		$currentItCount = $bcCategories->Count();
		$this->message('Categories Found: '.$currentItCount);
		sleep(1);
		$this->_saveCategories($bcCategories, $allDbCategoryIDs);

		$notification = [
			'Total BigCommerce Categories Pulled: '.$totalBcCats,
			$this->_statsLog['Updated']+$this->_statsLog['Created']+$this->_statsLog['Removed'].' Categories Synced',
			$this->_statsLog['Updated'].' Categories Updated',
			$this->_statsLog['Created'].' Categories Created'
		];
		Notification::NotifyAll(implode("<br />", $notification));
		$this->message($notification);
	}

	public function _syncAllCategories($request)
	{
		$this->message('Syncing Records from BigCommerce');
		$bcCategoryEntity = Category::singleton()->Entity();
		$this->message('Retrieving Database Categories');
		$allDbCategoryIDs = Category::get()->Column('ID');
		$this->message(count($allDbCategoryIDs).' Database Categories Found');
		sleep(2);

		if ( ($request instanceof HTTPRequest) && (!$page = $request->getVar('page')) )
		{
			$page = 1;
		}
		$this->_count = count($allDbCategoryIDs);
		$params = ['page' => $page, 'limit' => 100];
		$this->message('Retrieving BigCommerce Categories | Page: '.$params['page']);
		$bcCategories = $bcCategoryEntity::getAll(true,$params);
		while($bcCategories->Count())
		{
			$currentItCount = $bcCategories->Count();
			$this->message('Page '.$params['page'].' Categories Found: '.$currentItCount);

			$this->_saveCategories($bcCategories, $allDbCategoryIDs);

			$params['page']++;
			$this->message('Retrieving BigCommerce Categories | Page: '.$params['page']);
			$bcCategories = $bcCategoryEntity::getAll(false,$params);
			$this->message('Page '.$params['page'].' Categories Found: '.$bcCategories->Count());
			sleep(1);
		}
//		if ( (!$request->getVar('page')) && (count($this->_syncedIds)) )
//		{
//			sleep(2);
//			// remove left over categories
//			$removeCategories = Category::get()->Exclude('ID',$this->_syncedIds);
//			$this->message($removeCategories->Count().' Categories to remove');
//			$this->_count = $removeCategories->Count();
//			foreach($removeCategories as $category)
//			{
//				$this->_count--;
//				$this->message($this->_count.' - Removing: [BigID:'.$category->BigID.'|ID:'.$category->ID.']'.$category->Title);
//				$category->delete();
//				$this->_statsLog['Removed']++;
//			}
//		}

		$notification = [
			'Total BigCommerce Categories Pulled: '.$totalBcCats,
			$this->_statsLog['Updated']+$this->_statsLog['Created']+$this->_statsLog['Removed'].' Categories Synced',
			$this->_statsLog['Updated'].' Categories Updated',
			$this->_statsLog['Created'].' Categories Created',
			$this->_statsLog['Removed'].' Categories Removed'
		];
		Notification::NotifyAll(implode("<br />", $notification));
		$this->message($notification);
	}
}