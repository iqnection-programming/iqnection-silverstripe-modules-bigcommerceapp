<?php

namespace IQnection\BigCommerceApp\Traits;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

trait Entity
{
	public function validate() 
	{
		return $this->_validate();
	}
	
	public function _validate() 
	{
		$result = new ValidationResult();
		return $result;
	}
	
	public function jsonSerialize()
	{
		return $this->toMap();
	}
	
	/**
	 * Syncs/Pushes the entity data with BigCommerce
	 * @returns object $this
	 */	
	public function Sync()
	{
		return $this->_sync();
	}
	
	public function _sync() 
	{
		$result = $this->validate();
		if (!$result->isValid())
		{
			throw ValidationException::create($result);
		}
		return $this;
	}
	
//	public function getFrontEndRequiredFields()
//	{
//		return $this->_getFrontEndRequiredFields();
//	}
//	
//	public function _getFrontEndRequiredFields()
//	{
//		return Forms\RequiredFields::create();
//	}
//	
//	public function getFrontEndFields()
//	{
//		return $this->_getFrontEndFields();
//	}
//	
//	public function _getFrontEndFields()
//	{
//		return Forms\FieldList::create();
//	}
	
	public function dropdownTitle()
	{
		return $this->name;
	}
	
	public function forDropdown()
	{
		return [];
	}
	
	public function delete() { }
	
	public function loadApiData($data)
	{
		return $this->_loadApiData($data);
	}
	
	public function _loadApiData($data)
	{
		$this->array = [];
		$this->api_data = $data;
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
	
	public function ApiClient()
	{
		if ($clientClass = $this->Config()->get('client_class'))
		{
			return Client::inst()->Api(md5($clientClass), $clientClass);
		}
	}
}












