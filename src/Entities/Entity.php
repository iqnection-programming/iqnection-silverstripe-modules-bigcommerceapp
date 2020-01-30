<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\View\ArrayData;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;

class Entity extends ArrayData implements \JsonSerializable
{
	public function ApiData() 
	{
		$data = $this->toMap();
		if ( (!$data['id']) && ($data['BigID']) )
		{
			$data['id'] = $data['BigID'];
		}
		$this->extend('updateApiData', $data);
		return $data;
	}
	
	public function jsonSerialize()
	{
		return $this->toMap();
	}
	
	public function loadApiData($data)
	{
		$this->array = [];
		if ( (is_object($data)) && (method_exists($data, 'get')) )
		{
			foreach($data->get() as $key => $value)
			{
				if ( (is_object($value)) && (method_exists($value, 'get')) )
				{
					$value = $this->buildArrayData($value);
				}
				elseif (is_array($value))
				{
					$newValue = ArrayList::create();
					foreach($value as $subValue)
					{
						$newSubInst = Injector::inst()->create(static::class, []);
						$newSubInst->loadApiData($subValue);
						$newValue->push($newSubInst);
					}
					$value = $newValue;
				}
				$this->setField($key, $value);
				if (is_string($value))
				{
					$this->setField($key, trim($value));
//					$this->setField('match-'.$key, preg_replace('/[^a-zA-Z0-9]/','',strtolower(trim($value))));
				}
			}
		}
		return $this;
	}
	
	protected function buildArrayData($data)
	{
		$arrayData = Injector::inst()->create(static::class, []);
		if ( (is_object($data)) && (method_exists($data, 'get')) )
		{
			foreach($data->get() as $key => $value)
			{
				if ( (is_object($value)) && (method_exists($value, 'get')) )
				{
					$value = $this->buildArrayData($value);
				}
				$arrayData->setField($key, $value);
				if (is_string($value))
				{
					$arrayData->setField($key, trim($value));
					$arrayData->setField('match-'.$key, preg_replace('/[^a-zA-Z0-9]/','',strtolower(trim($value))));
				}
			}
		}
		return $arrayData;
	}
}