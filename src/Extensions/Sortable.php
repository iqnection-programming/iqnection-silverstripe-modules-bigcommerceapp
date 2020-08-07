<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

class Sortable extends DataExtension
{
	private static $db = [
		'SortOrder' => 'Int'
	];
	
	private static $default_sort = 'SortOrder ASC, ID DESC';
	
	public function updateCMSFields($fields)
	{
		$fields->removeByName('SortOrder');
	}
	
	public function updateFrontEndFields($fields)
	{
		$fields->removeByName('SortOrder');
	}
	
	public function onBeforeWrite()
	{
		if ( (is_null($this->owner->SortOrder)) || (!$this->owner->Exists()) )
		{
			$className = $this->owner->getClassName();
			$this->owner->SortOrder = $className::get()->Count() + 1;
		}
	}
	
	public function isSortable()
	{
		return true;
	}
}