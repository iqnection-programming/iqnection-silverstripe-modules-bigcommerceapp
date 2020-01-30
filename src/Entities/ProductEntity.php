<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;

class ProductEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-product';
	
	public function ApiData() 
	{
		$data = parent::ApiData();
		if ($data['BigID'])
		{
			$data['id'] = $data['BigID'];
		}
		else
		{
			$data['type'] = 'physical';
			$data['weight'] = ($data['weight']) ? $data['weight'] : 0;
			$data['price'] = ($data['price']) ? $data['price'] : 0;
		}
		return $data;
	}
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$apiData = $this->ApiData();
		$id = $apiData['id'];
		if ($id)
		{
			$response = $apiClient->updateProduct($id, new \BigCommerce\Api\v3\Model\ProductPut($apiData));
		}
		else
		{
			$response = $apiClient->createProduct(new \BigCommerce\Api\v3\Model\ProductPost($apiData));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public static function getProducts($filter = [])
	{
		$filterKey = md5(json_encode($filter));
		$cacheName = self::generateCacheKey($this->Config()->get('cache_name').'-'.$filterKey);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) )
		{
			$inst = self::singleton();
			$cachedData = ArrayList::create();
			$apiClient = $inst->ApiClient();
			foreach($apiClient->getProducts($filter)->getData() as $bcRecord)
			{
				$cachedData->push($inst->buildArrayData($bcRecord));
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public function Search($filters)
	{
		return $this->getProducts($filters);
	}
	
	public static function FeaturedProducts()
	{
		$filters = [
			'is_featured' => 1,
			'is_visible' => true,
			'availability' => 'available'			
		];
		return self::getProducts($filters);
	}
		
	public static function forDropdown()
	{
		$records = [];
		foreach(self::getProducts() as $bcRecord)
		{
			$records[$bcRecord->id] = $bcRecord->name;
		}
		return $records;
	}
}