<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class ModifierEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-modifiers';
	private static $_childEntitiesMap = [
		'option_values' => ModifierValueEntity::class
	];
	
	public function ApiData()
	{
		$data = parent::ApiData();
//		if (is_array($data['option_values']))
//		{
//			$apiOptionValues = [];
//			foreach($this->option_values as $optionValue)
//			{
//				$apiOptionValues[] = $optionValue->ApiData();
//			}
//			$data['option_values'] = $apiOptionValues;
//		}
		if (!$data['config'])
		{
			$data['config'] = [];
		}
		return $data;
	}
	
	public function loadApiData($data)
	{
		parent::loadApiData($data);
		if (!$this->option_values)
		{
			$this->option_values = ArrayList::create();
		}
		return $this;
	}
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		$id = $data['id'] ? $data['id'] : ($data['BigID'] ? $data['BigID'] : null);
		
		if ( ($id) && ($data['product_id']) )
		{
			$response = $apiClient->updateModifier($data['product_id'], $id, new \BigCommerce\Api\v3\Model\ModifierPut($data));
		}
		else
		{
			$response = $apiClient->createModifier($data['product_id'],new \BigCommerce\Api\v3\Model\ModifierPost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
/*	public function modifierValues($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'), $this->id, $this->product_id);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			if ($this->id)
			{
				$apiClient = $this->ApiClient();
				$apiDataResponse = $apiClient->getModifierValues($productID, $this->id);
				foreach($apiDataResponse->getData() as $apiData)
				{
					$newInst = Injector::inst()->create(ModifierValue::class, []);
					$newInst->loadApiData($apiData);
					$cachedData->push($newInst);
				}
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}*/
		
	public static function getModifiers($productID,$refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').$productID);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			$apiDataResponse = $apiClient->getModifiers($productID,['page' => $page, 'limit' => 100]);
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
				$apiDataResponse = $apiClient->getModifiers($productID,['page' => $page, 'limit' => 100]);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
}









