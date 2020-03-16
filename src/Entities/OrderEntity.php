<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class OrderEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity;
	
	private static $client_class = \IQnection\BigCommerceApp\ClientV2::class;
	private static $api_class = \IQnection\BigCommerceApp\ClientV2::class;
	private static $cache_name = 'bigcommerce-order';
	private static $cache_lifetime = 600;
		
	public static function getById($id, $refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),$id);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (empty($cachedData)) || ($refresh) )
		{
			$cachedData = Injector::inst()->create(static::class, []);
			$apiClient = $cachedData->ApiClient();
			if ($apiResponse = $apiClient->getOrder($id))
			{
				$apiData = $apiResponse->getUpdateFields();
				$cachedData->loadApiData((array) $apiData);
				self::toCache($cacheName, $cachedData);
			}
		}
		return $cachedData;
	}
}












