<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Model\ApiObjectInterface;
use IQnection\BigCommerceApp\Model\Category;
use IQnection\BigCommerceApp\Extensions\HasMetafields;

class WidgetPlacement extends DataObject implements ApiObjectInterface
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $entity_class = \IQnection\BigCommerceApp\Entities\WidgetPlacementEntity::class;
	
	private static $table_name = 'BCWidgetPlacement';
	
	private static $extensions = [
		ApiObject::class
    ];
	
	private static $db = [
		'region' => 'Varchar(255)',
		'template_file' => 'Varchar(255)',
		'status' => "Enum('active,inactive','active')",
		'entity_id' => 'Int(11)'
	];
	
	private static $has_one = [
		'Widget' => Widget::class
	];
	
	private static $default_sort = 'ID ASC';
	
	private static $defaults = [
		'status' => 'active'
	];
	
	private static $readonly_fields = [
		'template_file',
		'region'
	];
	
	public function ApiData() 
	{
		$data = [
			'widget_uuid' => $this->Widget()->BigID,
			'region' => $this->region,
			'template_file' => $this->template_file,
			'entity_id' => $this->entity_id,
			'status' => $this->status
		];
		if ($this->BigID)
		{
			$data['uuid'] = $this->BigID;
		}
		$this->extend('updateApiData',$data);
		return $data;
	}
		
	public function loadApiData($data)
	{
		if ($data)
		{
			$this->BigID = $data->uuid;
			if ($widget = Widget::get()->Find('BigID',$data->widget_uuid))
			{
				$this->widget_uuid = $widget->BigID;
			}
			$this->template_file = $data->template_file;
			$this->region = $data->region;
			$this->entity_id = $data->entity_id;
			$this->status = $data->status;
		}
		else
		{
			$this->BigID = null;
		}
		$this->invokeWithExtensions('updateLoadFromApi',$data);
		return $this;
	}
	
	public function DropdownTitle()
	{
		return ($resource = $this->getPlacementResource()) ? $resource->dropdownTitle() : $this->template_file;
	}
	
	protected $_placementResource;
	public function getPlacementResource()
	{
		if (is_null($this->_placementResource))
		{
			$this->_placementResource = false;
			if ( ($templateConfig = $this->Entity()->getTemplateConfig()) && ($DataObjectClass = $templateConfig->DataObjectClass) && ($entityID = $this->entity_id) )
			{
				$this->_placementResource = $DataObjectClass::get()->Find('BigID',$entityID);
			}
		}
		return $this->_placementResource;
	}
}











