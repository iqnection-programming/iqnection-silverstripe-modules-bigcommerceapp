<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class Category
{
	use \IQnection\BigCommerceApp\Traits\ApiModel,
		\IQnection\BigCommerceApp\Traits\Cacheable,
		\IQnection\BigCommerceApp\Traits\Entity;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-categories';
	
	public function ApiData() {}
	
	public function getCategories()
	{
		$cacheName = self::generateCacheKey($this->Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) )
		{
			$cachedData = ArrayList::create();
			$apiClient = $this->ApiClient();
			foreach($apiClient->getCategories()->getData() as $bcCategory)
			{
				$cachedData->push($this->buildArrayData($bcCategory));
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
		
	public function forDropdown()
	{
		$cats = [];
		foreach($this->getCategories() as $bcCategory)
		{
			$cats[$bcCategory->id] = $bcCategory->name;
		}
		return $cats;
	}
}