<?php

namespace IQnection\BigCommerceApp\Traits;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms;
use IQnection\BigCommerceApp\Client;
use IQnection\BigCommerceApp\App;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;


trait ApiModel
{
	protected $_isSyncing = false;
	public function onAfterWrite()
	{
		parent::onAfterWrite();
		if (!$this->_isSyncing)
		{
			$this->_isSyncing = true;
			$this->syncObject();
		}
	}
	
	abstract public function ApiData();
	
	public function ApiClient()
	{
		if ($clientClass = $this->Config()->get('client_class'))
		{
			return Client::inst()->Api(md5($clientClass), $clientClass);
		}
	}
	
	abstract public function sync();
	
	protected static $_can_push = true;
	public function syncObject()
	{
		try {
			if (!self::$_can_push)
			{
				return;
			}
			self::$_can_push = false;
			
			$data = $this->ApiData();

			$result = $this->sync($data);
			$this->loadFromApi($result);
			$this->write();	
		} catch (\Exception $e) {
			BcLog::info('Error pushing',$this);
			throw new \SilverStripe\ORM\ValidationException('Error saving: '.$e->getMessage());
		}
	}
	
	protected function buildArrayData($data)
	{
		$arrayData = ArrayData::create([]);
		if ( (is_object($data)) && (method_exists($data, 'get')) )
		{
			foreach($data->get() as $key => $value)
			{
				if ( (is_object($value)) && (method_exists($value, 'get')) )
				{
					$value = $this->buildArrayData($value);
				}
				$arrayData->setField($key, $value);
			}
		}
		return $arrayData;
	}
	
}