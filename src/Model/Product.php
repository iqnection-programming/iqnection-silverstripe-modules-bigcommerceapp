<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Model\ApiObjectInterface;
use IQnection\BigCommerceApp\Model\Category;
use IQnection\BigCommerceApp\Extensions\HasMetafields;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Controller;
use IQnection\BigCommerceApp\Archive\Archivable;
use IQnection\BigCommerceApp\Cron\BackgroundJob;

class Product extends DataObject implements ApiObjectInterface
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $entity_class = \IQnection\BigCommerceApp\Entities\ProductEntity::class;
	
	private static $table_name = 'BCProduct';
	
	private static $extensions = [
		ApiObject::class,
		HasMetafields::class,
		Archivable::class
    ];
	
	private static $db = [
		'sort_order' => 'Int(11)',
		'sku' => 'Varchar(255)',
		'is_visible' => 'Boolean',
	];
	
	private static $belongs_many_many = [
		'Categories' => Category::class
	];
	
	private static $default_sort = 'sort_order ASC';
	
	private static $indexes = [
		'sku' => true,
	];
	
	private static $remove_fields = [
		'sort_order',
	];
	
	private static $readonly_fields = [
		'Title',
		'sku',
		'is_visible'
	];
	
	public function ApiData() 
	{
		$categoryIDs = array_filter($this->Categories()->Column('BigID'));
		$data = [
//			'name' => $this->Title,
//			'sku' => $this->sku,
//			'categories' => $categoryIDs,
		];
		if ($rawApiData = $this->RawApiData())
		{
//			$data['name'] = $rawApiData->name;
//			$data['sku'] = $rawApiData->sku;
		}
		if ($this->BigID)
		{
			$data['id'] = $this->BigID;
		}
		$this->invokeWithExtensions('updateApiData',$data);
		return $data;
	}
		
	public function loadApiData($data)
	{
		if ($data)
		{
			if ($data->id)
			{
				$this->BigID = $data->id;
				$this->Title = $data->name;
				$this->sku = $data->sku;
				$this->is_visible = $data->is_visible;
				$this->sort_order = $data->sort_order;
				if (is_array($data->categories))
				{
					$existingCategoryIDs = $this->Categories()->Column('BigID');
					$diff1 = array_diff($existingCategoryIDs, $data->categories);
					$diff2 = array_diff($data->categories,$existingCategoryIDs);
					if ( (count($diff1)) || (count($diff2)) )
					{
						$remove = $this->Categories()->Exclude('BigID',$data->categories);
						if ($remove->Count())
						{
							$this->Categories()->removeMany($remove->Column('ID'));
						}
						$add = Category::get()->Filter('BigID',$data->categories);
						if ($add->Count())
						{
							$this->Categories()->addMany($add->Column('ID'));
						}
					}
				}
			}
		}
		else
		{
//			$this->BigID = null;
		}
		$this->invokeWithExtensions('updateLoadApiData',$data);
		$this->write();
		return $this;
	}
	
	public function processWebhook($args)
	{
		$scope = $args['body']['scope'];
		$status = [];
		if (!$this->BigID)
		{
			$this->BigID = $args['BigID'];
		}
		try {
			switch($scope)
			{
				default:
					$this->Pull();
					$status[] = 'Pulled';
					break;
				
				case 'store/product/deleted':
					$this->delete();
					$status[] = 'Deleted';
					break;
			}
		} catch (\Exception $e) {
			$status[] = 'EXCEPTION';
			$status[] = $e->getMessage();
			if (method_exists($e, 'getResponseBody'))
			{
				$status[] = $e->getResponseBody();
			}
			print_r($status);
			throw $e;
		}
		print_r($status);
		return $status;
	}
	
	public function Pull() 
	{
		return $this->SyncFromApi();
	}
	
	public function SyncFromApi()
	{
		$Entity = $this->Entity();
		$Entity = $Entity::getByID($this->BigID, true, ['include' => 'custom_fields']);
		$this->invokeWithExtensions('onBeforeLoadFromApi', $Entity);
		$this->loadApiData($Entity);
		$this->invokeWithExtensions('onAfterLoadFromApi', $Entity);
		$this->write();
		return $this;
	}
	
	public function DropdownTitle()
	{
		return $this->Title;
	}
	
	public function Link($action = null)
	{
		if ($RawApiData = $this->RawApiData())
		{
			$link = Controller::join_links($RawApiData->custom_url->url,$action);
			$this->extend('updateLink',$link);
			return $link;
		}
	}
	
	public function AbsoluteLink($action = null)
	{
		if ($link = $this->Link($action))
		{
			$link = Controller::join_links(SiteConfig::current_site_config()->BigCommerceStoreUrl,$link);
			$this->extend('updateAbsoluteLink',$link);
			return $link;
		}
	}
	
	public function onAfterWrite()
	{
		parent::onAfterWrite();
		if ($this->NeedsSync)
		{
			BackgroundJob::CreateJob(static::class, 'Sync', ['ID' => $this->ID]);
		}
	}
}