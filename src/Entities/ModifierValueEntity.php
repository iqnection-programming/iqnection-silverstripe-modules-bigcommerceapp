<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class ModifierValueEntity extends Entity
{	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-modifier-values';
	
	public function ApiData()
	{
		$data = parent::ApiData();
		if ( (!$data['adjusters']) || (empty($data['adjusters'])) )
		{
			unset($data['adjusters']);
		}
		else
		{
//			$adjusters = array_filter($data['adjusters']);
//			if (count($adjusters) == 1)
//			{
//				unset($data['adjusters']);
//			}
		}
		if ( (!$data['config']) || (empty($data['config'])) )
		{
			unset($data['config']);
		}
		if ( (!$data['value_data']) || (empty($data['value_data'])) )
		{
			unset($data['value_data']);
		}
		return $data;
	}
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		$id = $data['id'] ? $data['id'] : ($data['BigID'] ? $data['BigID'] : null);
		
		if ($id)
		{
			$response = $apiClient->updateModifierValue($data['product_id'], $data['modifier_id'], $id, new \BigCommerce\Api\v3\Model\ModifierValuePut($data));
		}
		else
		{
			$response = $apiClient->createModifierValue($data['product_id'], $data['modifier_id'], new \BigCommerce\Api\v3\Model\ModifierValuePost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
}









