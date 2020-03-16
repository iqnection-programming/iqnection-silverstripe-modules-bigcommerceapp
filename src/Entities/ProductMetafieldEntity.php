<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class ProductMetafieldEntity extends MetafieldEntity
{
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $owner_resource_name = 'Product';
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		if ($data['id'])
		{
			$response = $apiClient->updateProductMetafield($data['id'], $this->getResourceID(), new \BigCommerce\Api\v3\Model\MetafieldPut($data));
		}
		else
		{
			$response = $apiClient->createProductMetafield($this->getResourceID(), new \BigCommerce\Api\v3\Model\MetafieldPost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
}









