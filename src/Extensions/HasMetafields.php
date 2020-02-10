<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\ORM\DataExtension;
use IQnection\BigCommerceApp\Entities\Metafield;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;

class HasMetafields extends DataExtension
{
	private static $metafield_class = MetafieldEntity::class;
}
