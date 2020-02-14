<?php

namespace IQnection\BigCommerceApp\Archive;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

class Archivable extends DataExtension
{
	public function onBeforeWrite()
	{
		if ( (!$this->owner->Exists()) && ($bigID = $this->owner->BigID) && ($archive = $this->owner->hasArchive()) )
		{
			$this->owner->ID = $archive->OriginalID;
			$archive->delete();
		}
	}
	
	public function onAfterWrite()
	{
		if ( ($bigID = $this->owner->BigID) && ($archive = $this->owner->hasArchive()) )
		{
			$archive->delete();
		}
	}
	
	public function onBeforeDelete()
	{
		$this->owner->Archive();
	}
	
	public function Archive()
	{
		if (!$archive = Archive::get()->Filter(['OriginalRecordClass' => $this->owner->getClassName(),'BigID' => $this->owner->BigID])->First())
		{
			$archive = Archive::create();
			$archive->OriginalID = $this->owner->ID;
			$archive->BigID = $this->owner->BigID;
			$archive->OriginalRecordClass = $this->owner->getClassName();
		}
		$this->owner->invokeWithExtensions('onBeforeArchive',$archive);
		
		$archive->ArchiveData = serialize($this->owner->toMap());
		
		$archive->write();
		$this->owner->invokeWithExtensions('onAfterArchive', $archive);
	}
	
	public function onBeforeArchive() { }
	
	public function onAfterArchive() { }
	
	public function hasArchive()
	{
		// see if we have an archived record
		return Archive::get()->Filter(['OriginalRecordClass' => $this->owner->getClassName(),'BigID' => $this->owner->BigID])->First();
	}
	
	public function onBeforeRestore() { }
	
	public function onAfterRestore() { }
}