<?php

namespace IQnection\BigCommerceApp\Cron;

use SilverStripe\Dev\BuildTask;
use IQnection\BigCommerceApp\Model\WidgetTemplate;
use IQnection\BigCommerceApp\Model\Widget;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;
use SilverStripe\Control\Director;
use IQnection\BigCommerceApp\Model\Product;
use IQnection\BigCommerceApp\Entities\ProductEntity;
use IQnection\BigCommerceApp\Model\Category;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Model\Notification;

class SyncProducts extends Sync
{
	protected $title = 'Sync BigCommerce Products';
	protected $description = 'Pulls products from BigCommerce';
	private static $segment = 'sync-bc-products';
	
	public function run($request = null)
	{
		$this->checkCli();
		$this->_syncAllProducts($request);
	}
	
	public function _syncAllProducts($request = null)
	{
		$this->message('Syncing Records from BigCommerce');
		$this->message('Retrieving Database Products');
		$allDbProductIDs = Product::get()->Column('ID');
		$totalRemaining = count($allDbProductIDs);
		$this->message($totalRemaining.' Database Products Found');
		sleep(3);
		$syncedIDs = [];
		$updated = 0;
		$created = 0;
		$removed = 0;
		$page = (($request instanceof HTTPRequest) && ($request->requestVar('page'))) ? $request->requestVar('page') : 1;
		$bcProducts = $this->getBcProducts($page,200);
		$this->message(count($bcProducts).' Products Received');
		$count = count($bcProducts);
		while(count($bcProducts))
		{
			$count = count($bcProducts);
			foreach($bcProducts as $bcProduct)
			{
				$count--;
				$totalRemaining--;
				$status = 'Updating';
				if (!$product = Product::get()->Find('BigID', $bcProduct->id))
				{
					$created++;
					$status = 'Creating';
					// create the new product
					$product = Product::create();
					$product->BigID = $bcProduct->id;
				}
				else
				{
					$updated++;
				}
				$product->invokeWithExtensions('loadApiData',$bcProduct);
				$this->message($totalRemaining.' - '.$count.' - '.$status.' Product: [BigID:'.$bcProduct->id.'|DBID:'.$product->ID.'] '.$bcProduct->name);
				$product->write();
				$syncedIDs[] = $product->ID;
			}
			$page++;
			sleep(3);
			$bcProducts = $this->getBcProducts($page);
			
			$this->message(count($bcProducts).' Products Received');
		}
		$this->message($updated+$created+$removed.' Products Synced');
		sleep(3);
		if (($request instanceof HTTPRequest) || (!$request->requestVar('page')))
		{
			// remove left over products
			$removeProducts = Product::get();
			if (count($syncedIDs))
			{
				$removeProducts = $removeProducts->Exclude('ID',$syncedIDs);
			}
			$this->message($removeProducts->Count().' Products to remove');
			$count = $removeProducts->Count();
			foreach($removeProducts as $product)
			{
				$count--;
				$this->message($count.' - Removing: '.$product->Title);
				$product->delete();
				$removed++;
			}
		}
		
		$notification = [
			$updated+$created+$removed.' Products Synced',
			$updated.' Products Updated',
			$created.' Products Created',
			$removed.' Products Removed'
		];
		Notification::NotifyAll(implode("<br />", $notification));
		$this->message($notification);
	}
	
	public function getBcProducts($page = 1, $limit = 200)
	{
		$cachedData = ArrayList::create();
		$inst = ProductEntity::singleton();
		$apiClient = $inst->ApiClient();
		$filters = [
			'page' => $page,
			'limit' => $limit,
			'include' => 'custom_fields'
		];
		$this->message('Retrieving '.$filters['limit'].' Products on Page: '.$filters['page']);
		$apiResponse = $apiClient->getProducts($filters);
		$apiRecords = $apiResponse->getData();
		foreach($apiRecords as $apiRecord)
		{
			$newInst = Injector::inst()->create(ProductEntity::class, []);
			$newInst->loadApiData($apiRecord);
			$cachedData->push($newInst);
		}
		$this->message('Total Products Received: '.$cachedData->Count());
		return $cachedData;
	}}