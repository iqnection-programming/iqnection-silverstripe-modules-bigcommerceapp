<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use IQnection\BigCommerceApp\Extensions\HasMetafieldEntities;
use SilverStripe\Core\Injector\Injector;

class ProductEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-product';
	private static $metafield_class = ProductMetafieldEntity::class;
	
	private static $extensions = [
		HasMetafieldEntities::class
	];
	
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
		$this->invokeWithExtensions('updateApiData', $data);
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
	
	public function CustomFields()
	{
		$cachedData = ArrayList::create();
		$inst = self::singleton();
		$apiClient = $inst->ApiClient();
		$page = 1;
		if (!isset($filters['page']))
		{
			$filters['page'] = $page;
		}
		if (!isset($filters['limit']))
		{
			$filters['limit'] = 100;
		}
		$apiResponse = $apiClient->getCustomFields($this->id, $filters);
		$responseMeta = $apiResponse->getMeta();
		while(($apiRecords = $apiResponse->getData()) && (count($apiRecords)))
		{
			foreach($apiRecords as $apiRecord)
			{
				$newInst = Injector::inst()->create(Entity::class, []);
				$newInst->loadApiData($apiRecord);
				$cachedData->push($newInst);
			}
			$page++;
			$filters['page'] = $page;
			if (count($apiRecords) < $filters['limit'])
			{
				break;
			}
			$apiResponse = $apiClient->getCustomFields($this->id, $filters);
		}
			
		return $cachedData;
	}
	
	public function Images()
	{
		$cachedData = ArrayList::create();
		$inst = self::singleton();
		$apiClient = $inst->ApiClient();
		$page = 1;
		if (!isset($filters['page']))
		{
			$filters['page'] = $page;
		}
		if (!isset($filters['limit']))
		{
			$filters['limit'] = 100;
		}
		$apiResponse = $apiClient->getProductImages($this->id, $filters);
		$responseMeta = $apiResponse->getMeta();
		while(($apiRecords = $apiResponse->getData()) && (count($apiRecords)))
		{
			foreach($apiRecords as $apiRecord)
			{
				$newInst = Injector::inst()->create(Entity::class, []);
				$newInst->loadApiData($apiRecord);
				$cachedData->push($newInst);
			}
			$page++;
			$filters['page'] = $page;
			if (count($apiRecords) < $filters['limit'])
			{
				break;
			}
			$apiResponse = $apiClient->getProductImages($this->id, $filters);
		}
			
		return $cachedData;
	}
	
	public static function getProducts($refresh = false, $filters = [])
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			if (!isset($filters['page']))
			{
				$filters['page'] = $page;
			}
			if (!isset($filters['limit']))
			{
				$filters['limit'] = 100;
			}
			$apiResponse = $apiClient->getProducts($filters);
			$responseMeta = $apiResponse->getMeta();
			while(($apiRecords = $apiResponse->getData()) && (count($apiRecords)))
			{
				foreach($apiRecords as $apiRecord)
				{
					$newInst = Injector::inst()->create(static::class, []);
					$newInst->loadApiData($apiRecord);
					$cachedData->push($newInst);
				}
				$page++;
				$filters['page'] = $page;
				$apiResponse = $apiClient->getProducts($filters);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function getById($id)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__.$id);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$inst = Injector::inst()->create(static::class, []);
			$apiClient = $inst->ApiClient();
			$apiResponse = $apiClient->getProductById($id);
			$apiRecords = $apiResponse->getData();
			$inst->loadApiData($apiRecord);
			self::toCache($cacheName, $inst);
		}
		return $cachedData;
	}
	
	public function Search($filters)
	{
		return $this->getProducts($filters);
	}
	
	public function modifiers($refresh = false)
	{
		if (!$id = $this->id)
		{
			$id = $this->BigID;
		}
		return ModifierEntity::getModifiers($id,$refresh);
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