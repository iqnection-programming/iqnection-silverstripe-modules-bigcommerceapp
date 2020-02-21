<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\View\ArrayData;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use IQnection\BigCommerceApp\Client;

class Entity extends ArrayData implements \JsonSerializable
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $_childEntitiesMap = [];
	protected $loadedData;
	
	public function ApiData() 
	{
		$data = $this->toMap();
		foreach($data as $key => &$value)
		{
			if ( (is_array($value)) && (count($value)) )
			{
				foreach($value as &$subValue)
				{
					if ( (is_object($subValue)) && (method_exists($subValue, 'ApiData')) )
					{
						$subValue = $subValue->ApiData();
					}
				}
			}
			elseif ( (is_object($value)) && (method_exists($value, 'ApiData')) )
			{
				$value = $value->ApiData();
			}
		}
		unset($data['api_data']);
		if ( (!$data['id']) && ($data['BigID']) )
		{
			$data['id'] = $data['BigID'];
		}
		$this->invokeWithExtensions('updateApiData', $data);
		return $data;
	}
	
	public function jsonSerialize()
	{
		return $this->ApiData();
	}
	
	public function loadApiData($data)
	{
		$this->array = [];
		$this->loadedData = $data;
		if ( (is_object($data)) && (method_exists($data, 'get')) )
		{
			$data = $data->get();
		}
		$childEntitiesMap = $this->owner->Config()->get('_childEntitiesMap');
		if (!is_array($data)) { return; }
		foreach($data as $key => $value)
		{
			if ( (is_object($value)) && (method_exists($value, 'get')) )
			{
				$value = $this->buildArrayData($value, $key);
			}
			elseif (is_array($value))
			{
				$childClass = Entity::class;
				if (array_key_exists($key, $childEntitiesMap))
				{
					$childClass = $childEntitiesMap[$key];
				}
				$newValue = ArrayList::create();
				foreach($value as $subValue)
				{
					$newSubInst = Injector::inst()->create($childClass, []);
					$newSubInst->loadApiData($subValue);
					$newValue->push($newSubInst);
				}
				$value = $newValue;
			}
			$this->setField($key, $value);
			if (is_string($value))
			{
				$this->setField($key, trim($value));
			}
		}
		return $this;
	}
	
	protected function buildArrayData($data, $key = null)
	{
		$childEntitiesMap = $this->owner->Config()->get('_childEntitiesMap');
		$childClass = Entity::class;
		if ( ($key) && (array_key_exists($key, $childEntitiesMap)) )
		{
			$childClass = $childEntitiesMap[$key];
		}
		$arrayData = Injector::inst()->create($childClass, []);
		$arrayData->loadApiData($data);
		return $arrayData;
	}
	
	public function dropdownTitle()
	{
		return $this->name;
	}
	
	public static function forDropdown()
	{
		return [];
	}
	
	public function delete()
	{
		user_error('delete called in '.get_class($this).', but not implemented');
	}
	
	public function ApiClient()
	{
		if ($clientClass = $this->Config()->get('client_class'))
		{
			return Client::inst()->Api(md5($clientClass), $clientClass);
		}
	}
}