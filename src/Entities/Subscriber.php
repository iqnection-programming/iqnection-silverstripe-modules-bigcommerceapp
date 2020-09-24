<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class SubscriberEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CustomersApi::class;
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		if ($data['id'])
		{
			$response = $apiClient->updateSubscriber($data['id'], new \BigCommerce\Api\v3\Model\SubscriberPost($data));
		}
		else
		{
			$response = $apiClient->createSubscriber(new \BigCommerce\Api\v3\Model\SubscriberPost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public static function getAll()
	{
		$data = ArrayList::create();
		$inst = self::singleton();
		$apiClient = $inst->ApiClient();
		$page = 1;
		$apiDataResponse = $apiClient->getSubscribers(['page' => $page, 'limit' => 100]);
		$responseMeta = $apiDataResponse->getMeta();
		while(($apiDatas = $apiDataResponse->getData()) && (count($apiDatas)))
		{
			foreach($apiDatas as $apiData)
			{
				$newInst = Injector::inst()->create(static::class, []);
				$newInst->loadApiData($apiData);
				$data->push($newInst);
			}
			$page++;
			$apiDataResponse = $apiClient->getSubscribers(['page' => $page, 'limit' => 100]);
		}
		return $data;
	}
	
	public static function getById($id)
	{
		$inst = Injector::inst()->create(static::class, []);
		$apiClient = $inst->ApiClient();
		$apiResponse = $apiClient->getBrandById($id);
		$apiRecords = $apiResponse->getData();
		$inst->loadApiData($apiRecord);
		return $inst;
	}
}









