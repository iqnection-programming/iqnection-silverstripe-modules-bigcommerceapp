<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use BigCommerce\Api\v3\Model\WidgetTemplateRequest;
use IQnection\BigCommerceApp\Model\BigCommerceLog;
use SilverStripe\Core\Injector\Injector;

class WidgetTemplateEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	private static $cache_name = 'bigcommerce-widgettemplate';
	
	public function ApiData() 
	{
		$data = parent::ApiData();
		if ( ($data['Title']) && (!$data['name']) )
		{
			$data['name'] = $data['Title'];
		}
		$this->extend('updateApiData',$data);
		return $data;
	}
	
	public function Sync()
	{
		$data = $this->ApiData();
		$id = $data['BigID'] ? $data['BigID'] : ($data['uuid'] ? $data['uuid'] : ($data['id'] ? $data['id'] : null));
		$Client = $this->ApiClient();
		if ($id)
		{
			$response = $Client->updateWidgetTemplate($id, new WidgetTemplateRequest( $data ) );
			BigCommerceLog::info('Template Created', $response);
		}
		else
		{
			$response = $Client->createWidgetTemplate( new WidgetTemplateRequest( $data ) );	
			BigCommerceLog::info('Template Updated', $response);			
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public function loadApiData($data)
	{
		$this->BidID = $data->uuid;
		return parent::loadApiData($data);
	}
	
	public function Unlink()
	{
		if ( ($id = $this->uuid) || ($id = $this->BigID) )
		{
			$client = $this->ApiClient();
			return $client->deleteWidgetTemplate($id);
		}
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
			$apiResponse = $apiClient->getWidgetTemplates(['page' => $page, 'limit' => 100]);
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
				$apiResponse = $apiClient->getWidgetTemplates(['page' => $page, 'limit' => 100]);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
}









