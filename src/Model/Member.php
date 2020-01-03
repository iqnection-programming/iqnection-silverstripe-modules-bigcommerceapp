<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;

class Member extends DataExtension
{
	private static $db = [
		'BigCommerceID' => 'Int',
	];
	
	private static $has_many = [
		'Notifications' => Notification::class
	];
	
	public function updateCMSFields(Forms\FieldList $fields)
	{
		$fields->replaceField('BigCommerceID', $fields->dataFieldByName('BigCommerceID')->performReadonlyTransformation());
	}
	
	public function FirstLetter()
	{
		return substr($this->owner->FirstName,0,1);
	}
}