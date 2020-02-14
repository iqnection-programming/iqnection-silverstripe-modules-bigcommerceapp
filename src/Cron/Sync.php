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
	protected $title = 'Sync With BigCommerce';
	protected $description = 'Looks for missing BigCommerce objects and adds them to the database';
	
	public function run($request)
	{
//		$this->syncWidgetTemplates();
//		$this->syncCategories();
		$this->syncProducts($request);
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
			$category->loadApiData($bcCategory);
			$category->write();
			usleep(500000);
		}
		$this->message($updated+$created.' Categories Synced');
		$this->message($updated.' Categories Updated');
		$this->message($created.' Categories Created');
	}
	
	public function syncProducts($request)
	{
		$this->message('Syncing Products from BigCommerce');
		$bcEntity = Product::singleton()->Entity();

		$apiClient = $bcEntity->ApiClient();
		$page = ($request->requestVar('page')) ? $request->requestVar('page') : 1;
		$filters['page'] = $page;
		$filters['limit'] = 100;
		$this->message('Retrieving BigCommerce Products Page '.$page);
		$apiResponse = $apiClient->getProducts($filters);
		$responseMeta = $apiResponse->getMeta();
		$updated = 0;
		$created = 0;
		while(($apiRecords = $apiResponse->getData()) && (count($apiRecords)))
		{
			foreach($apiRecords as $apiRecord)
			{
				$apiRecord = (object) $apiRecord->get();
				$status = 'Updating';
				if (!$product = Product::get()->Find('BigID', $apiRecord->id))
				{
					$created++;
					$status = 'Creating';
					// create the new category
					$product = Product::create();
					$product->BigID = $apiRecord->id;
				}
				else
				{
					$updated++;
				}
				$this->message($status.' Product '.($updated+$created).' : ['.$apiRecord->id.'] '.$apiRecord->name);
				$product->loadApiData($apiRecord);
				$product->write();
				usleep(500000);
			}
			$page++;
			$filters['page'] = $page;
			$this->message('Retrieving BigCommerce Products Page '.$page);
			sleep(1);
			$apiResponse = $apiClient->getProducts($filters);
		}
		
		$this->message($updated+$created.' Products Synced');
		$this->message($updated.' Products Updated');
		$this->message($created.' Products Created');
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
						$dbObject->loadApiData($widgetTemplate);
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