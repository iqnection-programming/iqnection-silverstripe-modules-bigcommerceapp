<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class CartEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CartApi::class;
	private static $cache_name = 'bigcommerce-cart';
	private static $_childEntitiesMap = [
		'line_items' => CartItemEntity::class
	];
	
	public function ApiData() 
	{
		$data = parent::ApiData();
		if ($data['BigID'])
		{
			$data['id'] = $data['BigID'];
		}
		$lineItems = [];
		foreach($data['line_items'] as $item)
		{
			if ($item instanceof CartItemEntity)
			{
				$item = $item->ApiData();
			}
			$lineItems[] = $item;
		}
		$data['line_items'] = $lineItems;
		
		$this->invokeWithExtensions('updateApiData', $data);
		return $data;
	}
	
	public function addItems($items = [])
	{
		$lineItems = [];
		foreach($items as $item)
		{
			if ($item instanceof CartItemEntity)
			{
				$item = $item->ApiData();
			}
			if (is_array($item))
			{
				$item = new \BigCommerce\Api\v3\Model\LineItemRequestData($item);
			}
			$lineItems[] = $item;
		}
		if (count($lineItems))
		{
			if (!$this->id)
			{
				$this->line_items = $lineItems;
				return $this->Sync();
			}
			$apiClient = $this->ApiClient();
			$response = $apiClient->cartsCartIdItemsPost($this->id, new \BigCommerce\Api\v3\Model\CartRequestData(['line_items' => $lineItems]));
			$this->loadApiData($response->getData());
			$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),$id);
			self::toCache($cacheName, $this);
		}
		return $this;
	}
	
	public function setCustomer($customerId)
	{
		if (!$this->id)
		{
			return $this->Sync();
		}
		$apiClient = $this->ApiClient();
		$apiData = $this->ApiData();
		$response = $apiClient->cartsCartIdPut($this->id, new \BigCommerce\Api\v3\Model\CartUpdateRequestData($apiData));
		$this->loadApiData($response->getData());
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),$id);
		self::toCache($cacheName, $this);
		return $this;
	}
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$apiData = $this->ApiData();
		$id = $apiData['id'];
		if ($id)
		{
//			$response = $apiClient->cartsCartIdPut($id, new \BigCommerce\Api\v3\Model\CartUpdateRequestData($apiData));
		}
		else
		{
			$response = $apiClient->cartsPost(new \BigCommerce\Api\v3\Model\CartCreateRequestData($apiData));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public function loadApiData($data)
	{
		parent::loadApiData($data);
		$lineItems = $this->line_items;
		foreach($this->line_items as $lineItem)
		{
			if ($physical_items = $lineItem->physical_items)
			{
				$lineItems = ArrayList::create();
				foreach($physical_items as $physical_item)
				{
					$newLineItem = CartItemEntity::create([]);
					$newLineItem->loadApiData($physical_item->loadedData);
					$lineItems->push($newLineItem);
				}
			}
		}
		$this->line_items = $lineItems;
		return $this;
	}
	
	public static function getById($id, $refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),$id);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$inst = Injector::inst()->create(static::class, []);
			$apiClient = $inst->ApiClient();
			$apiResponse = $apiClient->cartsCartIdGet($id);
			$apiRecord = $apiResponse->getData()->get();
			$inst->loadApiData($apiRecord);
			self::toCache($cacheName, $inst);
		}
		return $cachedData;
	}
}












