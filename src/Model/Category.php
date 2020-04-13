<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Model\ApiObjectInterface;
use IQnection\BigCommerceApp\Model\Product;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use IQnection\BigCommerceApp\Archive\Archivable;
use IQnection\BigCommerceApp\Cron\BackgroundJob;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class Category extends DataObject implements ApiObjectInterface
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $entity_class = \IQnection\BigCommerceApp\Entities\CategoryEntity::class;
	
	private static $table_name = 'BCCategory';
	
	private static $extensions = [
		ApiObject::class,
		Archivable::class,
		Hierarchy::class
    ];
	
	private static $db = [
		'description' => 'HTMLText',
		'sort_order' => 'Int(11)',
		'layout_file' => 'Varchar(255)',
		'is_visible' => 'Boolean',
	];
	
	private static $many_many = [
		'Products' => Product::class
	];
	
	private static $default_sort = 'sort_order ASC';
	
	private static $remove_fields = [
		'sort_order',
		'description'
	];
	
	private static $readonly_fields = [
		'Title',
		'sku'
	];
	
	public function CanDelete($member = null, $context = []) { return false; }
	
	public function getFrontEndFields($params = [])
	{
		$fields = parent::getFrontEndFields($params);
		
		$fields->removeByName([
			'Title',
			'ParentID',
			'sort_order',
			'is_visible',
			'layout_file'
		]);
		
		$this->extend('updateFrontEndFields',$fields);
//		$fields->unshift($fields->dataFieldByName('Title')->setAttribute('disabled','disabled'));
		
		return $fields;
	}
	
	public function onAfterWrite()
	{
		parent::onAfterWrite();
		if ($this->NeedsSync)
		{
			BackgroundJob::CreateJob(static::class, 'Pull', ['ID' => $this->ID]);
		}
	}
	
	public function ApiData() 
	{
		$data = [
//			'description' => $this->description,
//			'sort_order' => $this->sort_order,
			'name' => $this->Title
		];
		if ($parent = $this->Parent())
		{
			$data['parent_id'] = $parent->BigID;
		}
		if ($this->BigID)
		{
			$data['id'] = $this->BigID;
		}
		$this->extend('updateApiData',$data);
		return $data;
	}
	
	public function Parent()
	{
		return Category::get()->byID($this->ParentID);
	}
	
	public function loadApiData($data)
	{
		if ($data)
		{
			$this->BigID = $data->id;
			$this->Title = $data->name;
			$this->is_visible = $data->is_visible;
			if ($data->parent_id === 0)
			{
				$this->ParentID = 0;
			}
			elseif ($data->parent_id)
			{
				if ($parent = Category::get()->Find('BigID',$data->parent_id))
				{
					$this->ParentID = $parent->ID;
				}
			}
		}
		else
		{
			$this->BigID = null;
		}
		$this->invokeWithExtensions('updateLoadApiData',$data);
		return $this;
	}
	
	public function Children()
	{
		return Category::get()->Filter('ParentID', $this->ID);
	}
	
	public function Pull() 
	{
		return $this->SyncFromApi();
	}
	
	public function SyncFromApi()
	{
		$data = $this->Entity()->getByID($this->BigID);
		$this->invokeWithExtensions('loadApiData',$data);
		$this->write();
		return $this;
	}
	
	protected $_crumbs;
	public function Breadcrumbs()
	{
		if (is_null($this->_crumbs))
		{
			$breadcrumbs = $this->Title;
			if ($parent = $this->Parent())
			{
				$breadcrumbs = $parent->Breadcrumbs().' > '.$breadcrumbs;
			}
			$this->_crumbs = $breadcrumbs;
		}
		return $this->_crumbs;
	}
	
	public function DropdownTitle()
	{
		return $this->Breadcrumbs();
	}
	
	public function Link($action = null)
	{
		if ($RawApiData = $this->RawApiData())
		{
			$link = Controller::join_links(SiteConfig::current_site_config()->BigCommerceStoreUrl,$RawApiData->custom_url->url,$action);
			$this->extend('updateLink',$link);
			return $link;
		}
	}
	
	public function AbsoluteLink($action = null)
	{
		if ($link = $this->Link($action))
		{
			$this->extend('updateAbsoluteLink',$link);
			return $link;
		}
	}
}









