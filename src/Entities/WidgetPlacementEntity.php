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
			'template_file' => 'pages/brand',
			'Title' => 'Brand',
			'EntityClass' => BrandEntity::class,
			'Enabled' => true
		],
		'pages/brand' => [
			'Name' => 'pages/brands',
			'template_file' => 'pages/brands',
			'Title' => 'All Brands',
			'EntityClass' => false,
			'Enabled' => true
		],
		'pages/category' => [
			'Name' => 'pages/category',
			'template_file' => 'pages/category',
			'Title' => 'Category',
			'EntityClass' => CategoryEntity::class,
			'DataObjectClass' => Category::class,
			'Enabled' => true
		],
		'pages/page' => [
			'Name' => 'pages/page',
			'template_file' => 'pages/page',
			'Title' => 'Page',
			'EntityClass' => PageEntity::class,
			'Enabled' => false
		],
		'pages/product' => [
			'Name' => 'pages/product',
			'template_file' => 'pages/product',
			'Title' => 'Product',
			'EntityClass' => ProductEntity::class,
			'DataObjectClass' => Product::class,
			'Enabled' => true
		],
		'pages/cart' => [
			'Name' => 'pages/cart',
			'template_file' => 'pages/cart',
			'Title' => 'Cart',
			'EntityClass' => false,
			'Enabled' => true
		],
		'pages/home' => [
			'Name' => 'pages/home',
			'template_file' => 'pages/home',
			'Title' => 'Home',
			'EntityClass' => false,
			'Enabled' => true
		],
		'pages/search' => [
			'Name' => 'pages/search',
			'template_file' => 'pages/search',
			'Title' => 'Search',
			'EntityClass' => false,
			'Enabled' => true
		]
	];
	
	protected $_placementResource;
	public function getPlacementResource()
	{
		if (is_null($this->_placementResource))
		{
			$this->_placementResource = false;
			if ( ($templateConfig = $this->getTemplateConfig()) && ($templateConfig->EntityClass) && ($entityID = $this->entity_id) )
			{
				$this->_placementResource = $templateConfig->EntityClass::getById($entityID);
			}
		}
		return $this->_placementResource;
	}
	
	protected $_templateConfig;
	public function setTemplateConfig($config = null)
	{
		$this->_templateConfig = null;
		if ( (is_string($config)) && ($_config = self::getTemplateFiles()->Find('Name',$config)) )
		{
			$config = $_config;
		}
		if ($config)
		{
			$this->_templateConfig = $config;
			$this->template_file = $config->Name;
		}
		return $this;
	}
	
	public function getTemplateConfig()
	{
		if ( (!$this->_templateConfig) && ($this->template_file) )
		{
			$this->setTemplateConfig($this->template_file);
		}
		return $this->_templateConfig;
	}
	
	public function ApiData() 
	{
		$data = $this->toMap();
		$data['widget_uuid'] = $this->widget_uuid;
		$this->extend('updateApiData', $data);
		return $data;
	}
	
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
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		$id = $data['BigID'] ? $data['BigID'] : ($data['uuid'] ? $data['uuid'] : ($data['id'] ? $data['id'] : null));
		$request = new PlacementRequest( $data );
		if ($id)
		{
			$response = $apiClient->updatePlacement($id, $request );
		}
		else
		{
			$response = $apiClient->createPlacement( $request );
		}
		$this->loadApiData($response->getData());
		return $this;
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
	
	public static function PlacementsForWidget($widgetUUID, $refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__.$widgetUUID);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			$apiResponse =$apiClient->getPlacements([
				'widget_uuid' => $widgetUUID
			]);
			foreach($apiResponse->getData() as $apiPlacement)
			{
				$newInst = Injector::inst()->create(static::class, []);
				$newInst->loadApiData($apiPlacement);
				$cachedData->push($newInst);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public function loadApiData($data)
	{
		$this->BigID = $data['uuid'];
		$this->setTemplateConfig($data['template_file']);
		return $this;
	}
	
	/**
	 * API returns null
	 */
	public function delete()
	{
		if ( ($id = $this->BigID) || ($id = $this->uuid) || ($id = $this->id) )
		{
			$apiClient = $this->ApiClient();
			return $apiClient->deletePlacement($id);
		}
	}
	
}












