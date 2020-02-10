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
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-modifiers';
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		if ($data['id'])
		{
			$response = $apiClient->updateModifier($data['id'], new \BigCommerce\Api\v3\Model\ModifierPut($data));
		}
		else
		{
			$response = $apiClient->createModifier($data['product_id'],new \BigCommerce\Api\v3\Model\ModifierPost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
		
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









