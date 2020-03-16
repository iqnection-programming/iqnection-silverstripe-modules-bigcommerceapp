<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class CartItemEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\CartApi::class;
	private static $cache_name = 'bigcommerce-cart';
	
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
		$cartId = $apiData['cart_id'];
		if (!$id)
		{
			throw new \Exception('New items must be added through the CartEntity class object');
		}
		$response = $apiClient->cartsCartIdItemsItemIdPut($cartId, $id, new \BigCommerce\Api\v3\Model\CartUpdateRequest($apiData));
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public function delete()
	{
		if ( ($id = $this->BigID) || ($id = $this->id) )
		{
			$apiClient = $this->ApiClient();
			return $apiClient->cartsCartIdItemsItemIdDelete($this->cart_id, $id);
		}
	}
}









