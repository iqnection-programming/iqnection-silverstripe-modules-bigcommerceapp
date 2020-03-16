<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class CustomFieldEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-custom-field';
		
	public function ApiData() 
	{
		$data = parent::ApiData();
		if ($data['BigID'])
		{
			$data['id'] = $data['BigID'];
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
			$response = $apiClient->updateCustomField($id, new \BigCommerce\Api\v3\Model\CustomFieldPut($apiData));
		}
		else
		{
			$response = $apiClient->createCustomField($apiData['product_id'], new \BigCommerce\Api\v3\Model\CustomFieldPost($apiData));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public static function getAll($productID, $refresh = false, $filters = [])
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'), __FUNCTION__, $productID);
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
			$apiResponse = $apiClient->getCustomFields($productID, $filters);
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
				$apiResponse = $apiClient->getCustomFields($productID, $filters);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function getById($productID, $id, $refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'), __FUNCTION__, $productID, $id);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$inst = Injector::inst()->create(static::class, []);
			$apiClient = $inst->ApiClient();
			$apiResponse = $apiClient->getCustomFieldById($id);
			$apiRecord = $apiResponse->getData()->get();
			$inst->loadApiData($apiRecord);
			self::toCache($cacheName, $inst);
		}
		return $cachedData;
	}
}