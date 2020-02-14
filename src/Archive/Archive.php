<?php

namespace IQnection\BigCommerceApp\Archive;

use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector\Injector;

class Archive extends DataObject
{
	private static $table_name = 'BCArchive';
	
	private static $db = [
		'OriginalID' => 'Int',
		'BigID' => 'Varchar(255)',
		'OriginalRecordClass' => 'Varchar(255)',
		'ArchiveData' => 'Text'
	];
	
	public function Restore()
	{		
		$inst = Injector::inst()->create($this->OriginalRecordClass);
		$inst->invokeWithExtensions('onBeforeRestore');
		$inst->update(unserialize($this->ArchiveData));		
		$inst->ID = $this->OriginalID;
		$inst->BigID = $this->BigID;
		$inst->invokeWithExtensions('onAfterRestore');
		$inst->write();
		return $inst;
	}
	
	public static function RestoreFromArchive($className, $BigCommerceID)
	{
		$inst = null;
		if ($archive = Archive::get()->Filter(['OriginalRecordClass' => $className,'BigID' => $BigCommerceID])->First())
		{
			if ($inst = $archive->Restore())
			{
				$archive->delete();
			}
		}
		return $inst;
	}
}