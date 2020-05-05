<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;
use SilverStripe\Control\Director;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Entities\Entity;
use IQnection\BigCommerceApp\Cron\BackgroundJob;

class ApiObject extends DataExtension
{
	private static $db = [
		'BigID' => 'Varchar(255)',
		'Title' => 'Varchar(255)',
		'RawData' => 'Text',
		'NeedsSync' => 'Boolean',
		'LastSynced' => 'Datetime'
	];
	
	private static $defaults = [
		'Active' => true
	];
	
	private static $readonly_fields = [
		'BigID',
//		'Title'
	];
	
	private static $remove_fields = [
		'RawData',
		'NeedsSync',
		'LastSynced'
	];
	
	private static $entity_class;
	
	private static $frontend_required_fields = [];
	
	public function updateCMSFields($fields)
	{
		if ($removeFields = $this->owner->Config()->get('remove_fields'))
		{
			$fields->removeByName($removeFields);
		}
		
		foreach($this->owner->Config()->get('readonly_fields') as $fieldName)
		{
			if ($field = $fields->dataFieldByName($fieldName))
			{
				$fields->replaceField($fieldName, $field->performReadonlyTransformation());
			}
		}
		if ($this->owner->Exists())
		{
			$fields->addFieldToTab('Root.RawData', \IQnection\Forms\RawDataField::create('RawData',$this->owner->RawData));
		}
		return $fields;
	}
		
	public function updateFrontEndFields($fields)
	{
		if ($removeFields = $this->owner->Config()->get('remove_fields'))
		{
			$fields->removeByName($removeFields);
		}
		foreach($this->owner->Config()->get('readonly_fields') as $fieldName)
		{
			if ($field = $fields->dataFieldByName($fieldName))
			{
				$field->setAttribute('disabled','disabled');
				//$fields->replaceField($fieldName, $field->performReadonlyTransformation());
			}
		}
		$fields->removeByName(['RawData','BigID','Active','ID']);
//		$fields->dataFieldByName('Title')->setAttribute('disabled','disabled');
		if ($this->owner->BigID)
		{
			$fields->unshift( Forms\TextField::create('_BigID','id',$this->owner->BigID)->setAttribute('disabled','disabled') );
		}
		if ($this->owner->Exists())
		{
			$fields->push( Forms\HiddenField::Create('_ID','')
				->setValue($this->owner->ID) );
		}
		if ($this->owner->NeedsSync)
		{
			$fields->unshift( Forms\ReadonlyField::create('syncPending','')->setValue('Sync Pending') );
		}
		if ($this->owner->Exists())
		{
			$fields->unshift( Forms\ReadonlyField::create('LastSynced','')->setValue('Last Synced: '.$this->owner->dbObject('LastSynced')->Nice()) );
		}
		return $fields;
	}
	
	public function getFrontEndRequiredFields(Forms\FieldList $fields)
	{
		$requiredFields = [];
		foreach($this->owner->Config()->get('frontend_required_fields') as $requiredField)
		{
			if ($field = $fields->dataFieldByName($requiredField))
			{
				$requiredFields[] = $requiredField;
				$fields->dataFieldByName($requiredField)->addExtraClass('required');
			}
		}
		$requiredFields = Forms\RequiredFields::create($requiredFields);
		$this->owner->extend('updateFrontEndRequiredFields', $requiredFields);
		return $requiredFields;
	}
		
	public function RawApiData()
	{
		if ($data = json_decode($this->owner->RawData))
		{
			return ArrayData::create($data);
		}
	}
	
	public function debugRawData()
	{
		return print_r(json_decode($this->owner->RawData),1);
	}
	
	/**
	 * BigCommerce Model method to remove the object from BigCommerce, but not from our local database
	 */
	public function Unlink()
	{
		if ($entity = $this->Entity())
		{
			$entity->delete();
		}
	}
	
	public function ApiClient()
	{
		if ($inst = $this->owner->Entity())
		{
			return $inst->ApiClient();
		}
	}
	
	public function loadApiData($data)
	{
		$this->owner->invokeWithExtensions('updateLoadApiData',$data);
		return $this->owner;
	}
	
	public function updateLoadApiData($data)
	{
		if ( (is_object($data)) && (method_exists($data,'toMap')) )
		{
			$data = $data->toMap();
		}
		$this->owner->RawData = json_encode($data);
		$this->owner->LastSynced = date('Y-m-d H:i:s');
		$this->owner->NeedsSync = false;
	}
	
	public function Sync() 
	{
		$Entity = $this->owner->Entity();
		$this->owner->invokeWithExtensions('onBeforeSync', $Entity);
		$Entity->Sync();
		$this->owner->invokeWithExtensions('onAfterSync', $Entity);
		$this->owner->loadApiData($Entity);
		$this->owner->LastSynced = date('Y-m-d H:i:s');
		$this->owner->NeedsSync = false;
		$this->owner->write();
		return $Entity;
	}
	
	public function QueueSync()
	{
		BackgroundJob::CreateJob($this->owner->getClassName(), 'Sync', ['ID' => $this->owner->ID], 'QueuedSync');
		return $this;
	}
	
	public function Pull()
	{
		user_error('The method Pull is not implemented on '.static::class);
	}
	
	public function NewEntity()
	{
		if ($class = $this->owner->Config()->get('entity_class'))
		{
			return Injector::inst()->create($class, []);
		}
	}
	
	public function Entity()
	{
		if ($class = $this->owner->Config()->get('entity_class'))
		{
			if (is_null($this->owner->_entity))
			{
				$this->owner->_entity = $this->owner->NewEntity();
				$this->owner->_entity->loadApiData($this->owner->ApiData());
			}
		}
		return $this->owner->_entity;
	}
	
	public function onBeforeDelete()
	{
		$this->Unlink();
	}
}















