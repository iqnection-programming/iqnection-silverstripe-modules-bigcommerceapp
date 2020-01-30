<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use BigCommerce\Api\v3\Model\WidgetTemplateRequest;
use IQnection\BigCommerceApp\Model\BigCommerceLog;

class WidgetTemplateEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	private static $cache_name = 'bigcommerce-widgettemplate';
	
	protected static $_is_pushing = false;
	public function Sync()
	{
		$data = $this->ApiData();
		$id = $this->BigID ? $this->BigID : $data['id'];
		$Client = $this->ApiClient();
		if ($id)
		{
			$response = $Client->updateWidgetTemplate($id, new WidgetTemplateRequest( $data ) );
			BigCommerceLog::info('Template Created', $response);
		}
		else
		{
			$response = $Client->createWidgetTemplate( new WidgetTemplateRequest( $data ) );	
			BigCommerceLog::info('Template Updated', $response);			
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public function loadApiData($data)
	{
		$this->BidID = $data->uuid;
		return parent::loadApiData($data);
	}
	
	public function delete()
	{
		if ( ($id = $this->uuid) || ($id = $this->BigID) )
		{
			$client = $this->ApiClient();
			return $client->deleteWidgetTemplate($id);
		}
	}
	
	public function Unlink()
	{ }
}









