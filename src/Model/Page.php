<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class Page
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = null;//\BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-pages';
	
	public function ApiData() {}
	public function sync() {}
	
	public function getPages()
	{
		$cacheName = self::generateCacheKey($this->Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) )
		{
			$cachedData = ArrayList::create();
//			$apiClient = $this->ApiClient();
//			foreach($apiClient->getBrands()->getData() as $bcRecord)
//			{
//				$cachedData->push($this->buildArrayData($bcRecord));
//			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
		
	public function forDropdown()
	{
		$bcRecords = [];
		foreach($this->getPages() as $bcRecord)
		{
			$bcRecords[$bcRecord->id] = $bcRecord->name;
		}
		return $bcRecords;
	}
}