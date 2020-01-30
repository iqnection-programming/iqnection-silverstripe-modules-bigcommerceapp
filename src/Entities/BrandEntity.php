<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class BrandEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-brands';
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		if ($data['id'])
		{
			$response = $apiClient->updateBrand($data['id'], new \BigCommerce\Api\v3\Model\BrandPut($data));
		}
		else
		{
			$response = $apiClient->createBrand(new \BigCommerce\Api\v3\Model\BrandPost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
		
	public static function getBrands($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			$apiDataResponse = $apiClient->getBrands(['page' => $page, 'limit' => 100]);
			$responseMeta = $apiDataResponse->getMeta();
			while(($apiDatas = $apiDataResponse->getData()) && (count($apiDatas)))
			{
				foreach($apiDatas as $apiData)
				{
					$newInst = Injector::inst()->create(static::class, []);
					$newInst->loadApiData($apiData);
					$cachedData->push($newInst);
				}
				$page++;
				$apiDataResponse = $apiClient->getBrands(['page' => $page, 'limit' => 100]);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function forDropdown()
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = [];
			foreach(self::getBrands() as $bcCategory)
			{
				$cachedData[$bcCategory->id] = $bcCategory->name;
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
}









