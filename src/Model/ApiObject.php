<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms;

class ApiObject extends DataObject
{
	private static $client_class;
		
	private static $table_name = 'BCObject';
	
	private static $db = [
		'Active' => 'Boolean',
		'BigID' => 'Varchar(255)',
		'Title' => 'Varchar(255)',
		'RawData' => 'Text',
	];
	
	private static $summary_fields = [
		'Active.Nice' => 'Active',
		'Title' => 'Title',
	];
	
	private static $defaults = [
		'Active' => true
	];
	
	private static $readonly_fields = [
		'BigID'
	];
	
	private static $remove_fields = [
		'RawData'
	];
	
	private static $frontend_required_fields = [
		'Title'
	];
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		if ($removeFields = $this->Config()->get('remove_fields'))
		{
			$fields->removeByName($removeFields);
		}
		
		foreach($this->Config()->get('readonly_fields') as $fieldName)
		{
			if ($field = $fields->dataFieldByName($fieldName))
			{
				$fields->replaceField($fieldName, $field->performReadonlyTransformation());
			}
		}
		if ($this->Exists())
		{
			$fields->addFieldToTab('Root.RawData', \IQnection\Forms\RawDataField::create('RawData',$this->RawData));
		}
		return $fields;
	}
	
	public function loadFromApi($data)
	{
		if ($data)
		{
			$this->BigID = $data->getUuid();
		}
		else
		{
			$this->BigID = null;
		}
		$this->RawData = (string) $data;
		return $this;
	}
	
	public function getFrontEndFields($params = null)
	{
		$fields = parent::getFrontEndFields($params = null);
		$fields->removeByName(['RawData','BigID','Active','ID']);
		if ($this->BigID)
		{
			$fields->unshift( Forms\ReadonlyField::create('_BigID','id',$this->BigID) );
		}
		if ($this->Exists())
		{
			$fields->push( Forms\HiddenField::Create('_ID','')->setValue($this->ID) );
		}
		return $fields;
	}
	
	public function getFrontEndRequiredFields(Forms\FieldList &$fields)
	{
		$requiredFields = [];
		foreach($this->Config()->get('frontend_required_fields') as $requiredField)
		{
			if ($field = $fields->dataFieldByName($requiredField))
			{
				$requiredFields[] = $requiredField;
				$fields->dataFieldByName($requiredField)->addExtraClass('required');
			}
		}
		return Forms\RequiredFields::create($requiredFields);
	}
	
	/**
	 * BigCommerce Model method to remove the object from BigCommerce, but not from our local database
	 */
	public function Unlink()
	{
		user_error('Unlink called in '.get_class($this).', but not implemented');
	}
}















