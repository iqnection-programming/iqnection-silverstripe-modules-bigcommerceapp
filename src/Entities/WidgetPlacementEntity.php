<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms;
use IQnection\BigCommerceApp\Model\Category;
use IQnection\BigCommerceApp\Model\Brand;
use IQnection\BigCommerceApp\Model\Page;
use SilverStripe\Core\Injector\Injector;
use BigCommerce\Api\v3\Model\PlacementRequest;
use SilverStripe\ORM\FieldType;

class WidgetPlacementEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Cacheable,
		\SilverStripe\Core\Injector\Injectable,
		\SilverStripe\Core\Config\Configurable,
		\IQnection\BigCommerceApp\Traits\Entity;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	private static $cache_name = 'widget-placements';
	
	private static $template_files = [
		'pages/brand' => [
			'Name' => 'pages/brand',
			'Title' => 'Brand',
			'EntityClass' => BrandEntity::class,
			'Enabled' => true
		],
		'pages/brand' => [
			'Name' => 'pages/brands',
			'Title' => 'All Brands',
			'EntityClass' => false,
			'Enabled' => true
		],
		'pages/category' => [
			'Name' => 'pages/category',
			'Title' => 'Category',
			'EntityClass' => CategoryEntity::class,
			'Enabled' => true
		],
		'pages/page' => [
			'Name' => 'pages/page',
			'Title' => 'Page',
			'EntityClass' => PageEntity::class,
			'Enabled' => false
		],
		'pages/product' => [
			'Name' => 'pages/product',
			'Title' => 'Product',
			'EntityClass' => ProductEntity::class,
			'Enabled' => true
		],
		'pages/cart' => [
			'Name' => 'pages/cart',
			'Title' => 'Cart',
			'EntityClass' => false,
			'Enabled' => true
		],
		'pages/home' => [
			'Name' => 'pages/home',
			'Title' => 'Home',
			'EntityClass' => false,
			'Enabled' => true
		],
		'pages/search' => [
			'Name' => 'pages/search',
			'Title' => 'Search',
			'EntityClass' => false,
			'Enabled' => true
		]
	];
	
	public function ApiData() {}
	
	public function validate()
	{
		$result = $this->_validate();
		if (!$this->WidgetBigID)
		{
			$result->addError('Widget Not Found');
		}
		if (!$this->RegionName)
		{
			$result->addError('Please select a region');
		}
		if (!$this->TemplateFile)
		{
			$result->addError('Template File Not Found');
		}
		return $result;
	}
	
	public function sync() 
	{
		$this->_sync();
		$apiPlacementData = [
			'widget_uuid' => $this->WidgetBigID,
			'region' => $this->RegionName,
			'template_file' => $this->TemplateFile,
//			'sort_order' => 1,
			'status' => 'active'
		];
		if ($this->EntityID)
		{
			$apiPlacementData['entity_id'] = $this->EntityID;
		}
		$apiClient = $this->ApiClient();
		if ($this->BigID)
		{
			return $apiClient->updatePlacement($this->BigID, new PlacementRequest( $apiPlacementData ) );
		}
		else
		{
			return $apiClient->createPlacement( new PlacementRequest( $apiPlacementData ) );
		}
	}
	
	public static function getTemplateFiles()
	{
		$templates = ArrayList::create();
		foreach(self::Config()->get('template_files') as $template)
		{
			$templates->push(ArrayData::create($template));
		}
		return $templates;
	}
	
	public static function getContentRegions($templateFile)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),$templateFile);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) )
		{
			$singleton = self::singleton();
			$cachedData = ArrayList::create();
			$apiClient = self::singleton()->ApiClient();
			foreach($apiClient->getContentRegions(['templateFile' => $templateFile])->getData() as $bcRecord)
			{
				$cachedData->push($singleton->buildArrayData($bcRecord));
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function PlacementsForWidget($widgetUUID)
	{
		$apiClient = self::singleton()->ApiClient();
		$placementsData = $apiClient->getPlacements([
			'widget_uuid' => $widgetUUID
		]);
		$placements = ArrayList::create();
		foreach($placementsData->getData() as $placementData)
		{
			$data = $placementData->get();
			$placements->push(WidgetPlacementEntity::create([
				'api_data' => $data,
				'BigID' => $data['uuid'],
				'EntityID' => $data['entity_id'],
				'Status' => $data['status'],
				'TemplateFile' => $data['template_file'],
				'Template' => self::getTemplateFiles()->Find('Name',$data['template_file']),
				'Region' => $data['region'],
				'RegionName' => ucwords(preg_replace('/_/',' ',$data['region'])),
				'SortOrder' => $data['sort_order'],
				'Created' => FieldType\DBField::create_field(FieldType\DBDatetime::class, $data['date_created']),
				'LastEdited' => FieldType\DBField::create_field(FieldType\DBDatetime::class, $data['date_modified'])
			]));
		}
		return $placements;
	}
	
	public function getFrontEndFields()
	{
		$fields = $this->_getFrontEndFields();
		$fields->push( Forms\HeaderField::create('pages-header','Page',3)->addExtraClass('mb-0') );
		$fields->push( $selectionGroup = Forms\SelectionGroup::create('PageType', [])->removeExtraClass('mt-0') );
		foreach(self::getTemplateFiles()->Filter('Enabled',true) as $templateConfig)
		{
			$regions = [];
			foreach(self::getContentRegions($templateConfig->Name) as $regionRecord)
			{
				$regions[$regionRecord->name] = ucwords(preg_replace('/_/',' ',$regionRecord->name));
			}
			if (count($regions))
			{
				$selectionGroup->push( $selectionGroup_item = Forms\SelectionGroup_Item::create($templateConfig->Name, [], $templateConfig->Title) );
				if ( ($EntityClass = $templateConfig->EntityClass) && (class_exists($EntityClass)) )
				{
					$EntityClass = Injector::inst()->get($templateConfig->EntityClass);	
					$selectionGroup_item->push( Forms\DropdownField::create('Entity['.$templateConfig->Name.'][ID]', $templateConfig->Title)
						->setSource($EntityClass->Entity()->forDropdown())
						->setEmptyString('-- Select --') );
				}
				// get regions on the category template
				$selectionGroup_item->push( Forms\DropdownField::create('Entity['.$templateConfig->Name.'][Region]', 'Region')
					->setSource($regions)
					->setEmptyString('-- Select --') );
			}
		}
		return $fields;
	}
	
	public function PageTitle()
	{
		if ($this->Template)
		{
			if ( ($this->Template->EntityClass) && ($this->EntityID) )
			{
				$EntityClass = Injector::inst()->get($this->Template->EntityClass);
				$dropdownOptions = $EntityClass->forDropdown();
				if (array_key_exists($this->EntityID, $dropdownOptions))
				{
					return $dropdownOptions[$this->EntityID];
				}
			}
			return $this->Template->Title;
		}
	}
	
	/**
	 * API returns null
	 */
	public function delete()
	{
		if ($this->BigID)
		{
			$apiClient = self::singleton()->ApiClient();
			return $apiClient->deletePlacement($this->BigID);
		}
	}
	
}












