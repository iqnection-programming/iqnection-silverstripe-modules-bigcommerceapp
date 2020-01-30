<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;

class WidgetEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	private static $cache_name = 'bigcommerce-widgets';
		
	protected static $_is_pushing = false;
	public function Sync()
	{
		try {
			$Client = $this->ApiClient();
			$data = $this->ApiData();
			$request = new WidgetRequest( $data );
			BcLog::info('Widget Data for: '.$this->BigID, $request);
			if ($data['id'])
			{
				$widget = $Client->updateWidget($data['id'], $request )->getData();
				BcLog::info('Updated Widget', $data['id']);
			}
			else
			{
				$widget = $Client->createWidget( $request )->getData();	
				$this->BigID = $widget->getUuid();			
				BcLog::info('Created Widget ',$widget);
			}
			return $widget;
		} catch (\Exception $e) {
			BcLog::error('Exception syncing widget', $e->__toString());
			return false;
		}
	}
	
	public function Unlink()
	{ }
}









