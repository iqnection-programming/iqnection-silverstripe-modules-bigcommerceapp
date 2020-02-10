<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\Core\Extension;
use IQnection\BigCommerceApp\Entities\Metafield;
use SilverStripe\Core\Injector\Injector;

class HasMetafieldEntities extends Extension
{
	private static $metafields = [];
	
	private static $default_permission_set = 'app_only';
	
	public function MetafieldEntities($namespace = null)
	{
		$metafieldClass = $this->owner->Config()->get('metafield_class');
		$filter = [];
		if ($namespace)
		{
			$filter['namespace'] = $namespace;
		}
		return $metafieldClass::getAll($this->owner->id, $filter);
	}
}
