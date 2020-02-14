<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use BigCommerce\Api\v3\Model\WidgetRequest;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;

class WidgetEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	private static $cache_name = 'bigcommerce-widgets';
		
	protected static $_is_pushing = false;
	public function Sync()
	{
		$Client = $this->ApiClient();
		$data = $this->ApiData();
		try {
			$request = new WidgetRequest( $data );
			BcLog::info('Widget Data for: '.$this->BigID, $request);
			$id = ($data['uuid']) ? $data['uuid'] : ($data['id'] ? $data['id'] : ($data['BigID'] ? $data['BigID'] : null));
			if ($id)
			{
				$response = $Client->updateWidget($id, $request );
				BcLog::info('Updated Widget', $id);
			}
			else
			{
				$response = $Client->createWidget( $request );	
				BcLog::info('Created Widget ',$widget);
			}
		} catch (\Exception $e) {
			BcLog::error('Exception syncing widget', [$e->__toString(),$e->getResponseBody()]);
			throw $e;
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public static function getAll($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			$apiResponse = $apiClient->getWidgets(['page' => $page, 'limit' => 100]);
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
				$apiResponse = $apiClient->getWidgets(['page' => $page, 'limit' => 100]);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
		
	public function delete()
	{
		if ( ($id = $this->BigID) || ($id = $this->uuid) || ($id = $this->id) )
		{
			$apiClient = $this->ApiClient();
			return $apiClient->deleteWidget($id);
		}
	}
}









