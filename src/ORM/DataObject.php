<?php

namespace IQnection\BigCommerceApp\ORM;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;

class DataObject extends DataExtension
{
	public function getDashboardFields()
	{
		$fields = Forms\FieldList::create();
		if ($this->owner->Exists())
		{
			$fields->push(Forms\HiddenField::create('ID','')->setValue($this->owner->ID));
		}
		
		return $fields;
	}
	
	public function updateDashboardFields(Forms\FieldList $fields)
	{
		
	}
}