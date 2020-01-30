<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\Core\Extension;
use IQnection\BigCommerceApp\Entities\Metafield;
use SilverStripe\Core\Injector\Injector;

class HasMetafields extends Extension
{
	private static $metafield_namespace = 'silverstripe';
	private static $metafield_permission_set = 'app_only';	// app_only, read, write
	private static $metafield_class;
	private static $metafields = [];
	
	public function Metafields()
	{
		$metafieldClass = $this->owner->Config()->get('metafield_class');
		$metafield_namespace = $this->owner->Config()->get('metafield_namespace');
		$metafield_permission_set = $this->owner->Config()->get('metafield_permission_set');
		$metafieldEntities = $metafieldClass::getAll($this->owner->id, ['namespace' => $this->owner->Config('metafield_namespace')]);
		foreach($this->owner->Config()->get('metafields') as $metafieldName)
		{
			if (!$metafieldEntities->Find('key',$metafieldName))
			{
				// if the record doesn't exist from the API, 
				// create an empty record so we can always find it
				$newMetafield = Injector::inst()->create($metafieldClass, [
					'resource_id' => $this->owner->id,
					'namespace' => $metafield_namespace,
					'permission_set' => $metafield_permission_set,
					'key' => $metafieldName
				]);
				$metafieldEntities->push($newMetafield);
			}
		}
		return $metafieldEntities;
	}
}
