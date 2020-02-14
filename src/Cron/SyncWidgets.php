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

class SyncWidgets extends Sync
{
	protected $title = 'Sync BigCommerce Widgets';
	protected $description = 'Pulls widgets from BigCommerce';
	private static $segment = 'sync-bc-widgets';
	
	public function run($request)
	{
		$this->_syncWidgets();
	}
	
	public function _syncWidgets()
	{
		$this->message('Syncing Records from BigCommerce');
		$bcEntity = Widget::singleton()->Entity();
		$this->message('Retrieving BigCommerce Widgets');
		$bcRecords = $bcEntity::getAll(true);
		$this->message($bcRecords->Count().' BigCommerce Widgets Found');
		$this->message('Retrieving Database Widgets');
		$allDbIDs = Widget::get()->Column('ID');
		$this->message(count($allDbIDs).' Database Widgets Found');
		sleep(2);
		$syncedIDs = [];
		$updated = 0;
		$created = 0;
		$removed = 0;
		$count = count($allDbIDs);
		foreach($bcRecords as $bcRecord)
		{
			$count--;
			$status = 'Updating';
			if (!$dbRecord = Widget::get()->Find('BigID', $bcRecord->id))
			{
				$created++;
				$status = 'Creating';
				// create the new 
				$dbRecord = Widget::create();
				$dbRecord->BigID = $bcRecord->id;
			}
			else
			{
				$updated++;
			}
			$this->message($count.' - '.$status.' Widget: ['.$bcRecord->BigID.'] '.$bcRecord->name);
			$dbRecord->loadApiData($bcRecord);
			$dbRecord->invokeWithExtensions('loadApiData',$bcRecord);
			$dbRecord->write();
			$syncedIDs[] = $dbRecord->ID;
			
		}
		sleep(2);
		// remove left over 
		$removeDbRecords = Widget::get()->Exclude('ID',$syncedIDs);
		$this->message($removeDbRecords->Count().' Widgets to remove');
		$count = $removeDbRecords->Count();
		foreach($removeDbRecords as $removeDbRecord)
		{
			$count--;
			$this->message($count.' - Unlinking: '.$removeDbRecord->Title);
			$removeDbRecord->BigID = null;
			$removeDbRecord->write();
			$removed++;
		}
		
		$this->message($updated+$created+$removed.' Widgets Synced');
		$this->message($updated.' Widgets Updated');
		$this->message($created.' Widgets Created');
		$this->message($removed.' Widgets Removed');
	}
}