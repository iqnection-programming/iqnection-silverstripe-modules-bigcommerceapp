<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Model\ApiObjectInterface;
use IQnection\BigCommerceApp\Model\Product;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;

class Category extends DataObject implements ApiObjectInterface
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $entity_class = \IQnection\BigCommerceApp\Entities\CategoryEntity::class;
	
	private static $table_name = 'BCCategory';
	
	private static $extensions = [
		ApiObject::class
    ];
	
	private static $db = [
		'description' => 'HTMLText',
		'sort_order' => 'Int(11)',
		'layout_file' => 'Varchar(255)',
		'is_visible' => 'Boolean',
		'ParentID' => 'Int'
	];
	
	private static $many_many = [
		'Products' => Product::class
	];
	
	private static $default_sort = 'sort_order ASC';
	
	public function CanDelete($member = null, $context = []) { return false; }
	
	public function getFrontEndFields($params = [])
	{
		$fields = parent::getFrontEndFields($params);
		
		$fields->removeByName([
			'Title',
			'ParentID',
			'sort_order'
		]);
		\SilverStripe\Forms\HTMLEditor\HTMLEditorConfig::set_active_identifier('bigcommerce');
		if ($base_description = $fields->dataFieldByName('base_description'))
		{
			$base_description_config = \SilverStripe\Forms\HTMLEditor\HTMLEditorConfig::get('bigcommerce');
			$base_description_config = $base_description->setEditorConfig($base_description_config);
		}
		$this->extend('updateFrontEndFields',$fields);
//		$fields->unshift($fields->dataFieldByName('Title')->setAttribute('disabled','disabled'));
		
		return $fields;
	}
	
	public function ApiData() 
	{
		$data = [
			'description' => $this->description,
			'sort_order' => $this->sort_order,
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
	
	public function Unlink() {}
	
	public function loadFromApi($data)
	{
		if ($data)
		{
			$this->BigID = $data->id;
			$this->Title = $data->name;
			$this->description = $data->description;
			$this->sort_order = $data->sort_order;
			$this->is_visible = $data->is_visible;
			$this->layout_file = $data->layout_file;
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
		
		return $this;
	}
	
	public function SyncFromApi()
	{
		$data = $this->Entity()->getCategoryByID($this->BigID);
		$this->invokeWithExtensions('loadFromApi',$data);
		$this->write();
		return $this;
	}

	public function Children()
	{
		return self::get()->Filter('ParentID',$this->ID);
	}
	
	public function Breadcrumbs()
	{
		$breadcrumbs = $this->Title;
		if ($parent = $this->Parent())
		{
			$breadcrumbs = $parent->Breadcrumbs().' > '.$breadcrumbs;
		}
		return $breadcrumbs;
	}
	
	public function Link()
	{
		return $this->AbsoluteLink();
	}
	
	public function AbsoluteLink()
	{
		if ($RawApiData = $this->RawApiData())
		{
			return Controller::join_links(SiteConfig::current_site_config()->BigCommerceStoreUrl,$RawApiData->custom_url->url);
		}
	}
}









