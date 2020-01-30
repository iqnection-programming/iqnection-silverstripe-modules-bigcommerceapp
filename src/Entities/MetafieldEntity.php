<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class Metafield extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity;
	
	private static $client_class;
	private static $owner_resource_name;
	
	public function setResourceID($id)
	{
		$resource_id = $id;
		if (is_object($id))
		{
			if ($id instanceof \SilverStripe\ORM\DataObject)
			{
				$resource_id = $id->BigID;
			}
			if (!$resource_id)
			{
				$resource_id = $id->id;
			}
		}
		if (!is_numeric($resource_id))
		{
			throw new Exception(__FUNCTION__ .' requires an object with an ID, or ID', 100);
		}
		$this->setField('resource_id', $resource_id);
		return $this;
	}
	
	public function getResourceID()
	{
		return $this->getField('resource_id');
	}
	
	public function Sync() 
	{
		user_error(static::class.' Requires method Sync to sync data with BigCommerce');
	}
	
	public static function getAll($ownerID, $params = [])
	{
		$id = $ownerID;
		if (is_object($ownerID))
		{
			$id = $ownerID->id;
		}
		if (!is_numeric($id))
		{
			throw new \Exception(__FUNCTION__ .' requires an ID, or an object with an ID', 100);
		}
		try {
			$resourceType = static::Config()->get('owner_resource_name');
			$inst = Injector::inst()->get(static::class);
			$apiClient = $inst->ApiClient();
			$methodName = 'get'.$resourceType.'MetafieldsBy'.$resourceType.'Id';
			$response = $apiClient->{$methodName}($id, $params);
			$metaFields = ArrayList::create();
			foreach($response->getData() as $apiMetafield)
			{
				$metaFields->push( $metaField = static::create() );
				$metaField->loadApiData($apiMetafield);
			}
			return $metaFields;
		} catch (\Exception $e) {
			throw $e;
		}
	}
}









