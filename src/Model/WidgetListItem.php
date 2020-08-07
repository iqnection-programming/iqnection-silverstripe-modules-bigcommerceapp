<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use IQnection\BigCommerceApp\Extensions\ApiRelatedObject;

class WidgetListItem extends DataObject implements \JsonSerializable
{
	public $Sortable = true;
	
	private static $extensions = [
		ApiRelatedObject::class
	];
	
	private static $table_name = 'BCWidgetListItem';
	
	private static $db = [
		'Title' => 'Varchar(255)',
		'SortOrder' => 'Int'
	];
	
	private static $has_one = [
		'Widget' => Widget::class
	];
	
	private static $default_sort = 'SortOrder ASC';
	
	private static $frontend_required_fields = [];
	
	public function getFrontEndFields($params = [])
	{
		$fields = parent::getFrontEndFields();
		$fields->replaceField('SortOrder', Forms\HiddenField::create('SortOrder','')->setValue($this->SortOrder));
		$fields->removeByName([
			'ID',
			'WidgetID'
		]);
		if ($this->Exists())
		{
			$fields->push( Forms\HiddenField::create('RelatedID','')->setValue($this->ID) );
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
	
	public function Sortable()
	{
		return (bool) $this->Sortable;
	}
	
	public function onAfterWrite()
	{
		parent::onAfterWrite();
		if ($this->Widget()->Exists())
		{
			$this->Widget()->QueueSync();
		}
	}
	
	public function jsonSerialize()
	{
		$data = $this->WidgetData();
		if ($data instanceof ArrayData)
		{
			return $data->toMap();
		}
		return $data;
	}
	
	public function WidgetData()
	{
		return [
			'id' => $this->ID,
			'title' => $this->Title
		];
	}
	
	public function forTemplate()
	{
		$fields = $this->getFrontEndFields();
		$fields->setValues($this->toMap());
		$fields = $fields->makeReadonly();
		return $fields->forTemplate();
	}
	
	public function DashboardDisplay()
	{
		return ArrayList::create([
			ArrayData::create([
				'Title' => 'Name',
				'Value' => $this->Title
			])
		]);
	}
}








