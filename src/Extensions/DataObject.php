<?php

namespace IQnection\BigCommerceApp\Extensions;

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
		$this->owner->invokeWithExtensions('updateDashboardFields',$fields);
		return $fields;
	}
}