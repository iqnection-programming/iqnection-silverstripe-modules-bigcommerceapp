<?php


namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class ApiRelatedObject extends DataExtension
{
	private static $frontend_required_fields = [];
	
	public function DashboardDisplay()
	{
		user_error('Method DashboardDisplay not found in class '.get_class($this->owner));
	}
	
	public function onBeforeWrite()
	{
		// remove versioning from images in HTML Editors
		foreach($this->owner->getSchema()->fieldSpecs($this) as $fieldName => $fieldType)
		{
			if (in_array($fieldType,['HTMLText','HTMLVarchar']))
			{
				$this->owner->{$fieldName} = $this->StripImageVersions($this->owner->{$fieldName});
			}
		}
	}
	
	public function StripImageVersions($html)
	{
		return preg_replace('/(img[^>]+src=[\'\"][^\'\"\?]+)\?.*?([\'\"])/','$1$2',$html);
	}
	
	public function RelatedObjects()
	{
		$relations = ArrayList::create();
		$this->owner->invokeWithExtensions('updateRelatedObjects', $relations);
		return $relations;
	}
	
	public function updateFrontEndFields(Forms\FieldList $fields)
	{
		$fields->push( Forms\HiddenField::create('RelatedID','')->setValue($this->owner->ID) );
		$fields->push( Forms\HiddenField::create('ComponentName','') );
		return $fields;
	}
	
	public function forTemplate()
	{
		$fields = $this->getFrontEndFields();
		$fields->setValues($this->toMap());
		$fields = $fields->makeReadonly();
		return $fields->forTemplate();
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
	
	public function updateFrontEndRequiredFields($requiredFields) { }
}