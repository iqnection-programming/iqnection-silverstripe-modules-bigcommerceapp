<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Entities\MetafieldEntity;
use SilverStripe\Forms;
use SilverStripe\Core\Injector\Injector;

class Metafield extends DataExtension
{
	private static $entity_class = MetafieldEntity::class;
	
	private static $db = [
		'BigID' => 'Int',
		'NeedsSync' => 'Boolean',
		'value' => 'Text',
		'namespace' => 'Varchar(255)',
		'permission_set' => 'Varchar(20)',
		'key' => 'Varchar(255)',
		'RawData' => 'Text',
		'resource_type' => 'Varchar(255)',
		'resource_id' => 'Int'
	];
	
	private static $has_one = [
		'Master' => DataObject::class
	];
	
	public function updateFrontEndFields(Forms\FieldList $fields)
	{
		$fields->dataFieldByName('BigID')->setAttribute('readonly','readonly');
		$fields->removeByName([
			'namespace',
			'permission_set',
			'key',
			'RawData',
			'resource_type',
			'resource_id',
			'SortOrder',
			'NeedsSync'
		]);
		return $fields;
	}
	
	public function ApiData()
	{
		return $this->owner->MetafieldApiData();
	}
	
	public function MetafieldApiData()
	{
		$data = [
			'id' => $this->owner->BigID,
			'resource_id' => $this->owner->Master()->BigID,
			'description' => $this->owner->Config()->get('description'),
			'key' => $this->owner->key,
			'value' => $this->owner->value,
			'permission_set' => $this->owner->Config()->get('permission_set'),
			'namespace' => $this->owner->Config()->get('namespace'),
		];
		$this->owner->invokeWithExtensions('updateApiData',$data);
		return $data;
	}
	
	public function loadFromApi($data)
	{
		$this->owner->invokeWithExtensions('updateLoadFromApi',$data);
		return $this->owner;
	}
	
	public function updateLoadFromApi($data)
	{
		if ($data)
		{
			$this->owner->BigID = $data->id;
			$this->owner->value = $data->value;
			$this->owner->key = $data->key;
			$this->owner->namespace = $data->namespace;
			$this->owner->permission_set = $data->permission_set;
			$this->owner->resource_type = $data->resource_type;
			$this->owner->resource_id = $data->resource_id;
			$this->owner->LastSynced = date('Y-m-d H:i:s');
		}
		else
		{
			$this->owner->BigID = null;
		}
		$this->owner->RawData = json_encode($data);
	}
	
	public function Sync() 
	{
		$Entity = $this->owner->Entity();
		$this->owner->invokeWithExtensions('onBeforeSync', $Entity);
		$Entity->Sync();
		$this->owner->invokeWithExtensions('onAfterSync', $Entity);
		$this->owner->loadFromApi($Entity);
		$this->owner->LastSynced = date('Y-m-d H:i:s');
		$this->owner->NeedsSync = false;
		$this->owner->write();
		return $Entity;
	}
	
	public function NewEntity()
	{
		if ($class = $this->owner->Config()->get('entity_class'))
		{
			return Injector::inst()->create($class, $this->owner->ApiData());
		}
	}
	
	public function Entity()
	{
		if ($class = $this->owner->Config()->get('entity_class'))
		{
			if (is_null($this->owner->_entity))
			{
				$this->owner->_entity = $this->owner->NewEntity();
			}
		}
		return $this->owner->_entity;
	}
	
}