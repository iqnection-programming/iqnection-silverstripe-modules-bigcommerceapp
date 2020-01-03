<?php

namespace IQnection\BigCommerceApp\Traits;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;

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
		
	public function sync()
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
	
	public function getFrontEndRequiredFields()
	{
		return $this->_getFrontEndRequiredFields();
	}
	
	public function _getFrontEndRequiredFields()
	{
		return Forms\RequiredFields::create();
	}
	
	public function getFrontEndFields()
	{
		return $this->_getFrontEndFields();
	}
	
	public function _getFrontEndFields()
	{
		return Forms\FieldList::create();
	}
	
	public function forDropdown()
	{
		return [];
	}
	
	public function delete() { }
}












