<?php


namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;

class ApiRelatedObject extends DataExtension
{
	public function DashboardDisplay()
	{
		user_error('Method DashboardDisplay not found in class '.get_class($this->owner),500);
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
}