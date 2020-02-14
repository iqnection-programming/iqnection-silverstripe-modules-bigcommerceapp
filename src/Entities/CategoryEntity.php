<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class CategoryEntity extends Entity 
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $extensions = [
		\IQnection\BigCommerceApp\Extensions\HasMetafieldEntities::class
	];
	
	private static $client_class = \BigCommerce\Api\v3\Api\CatalogApi::class;
	private static $cache_name = 'bigcommerce-categories';
	private static $metafield_class = CategoryMetafieldEntity::class;
	
	public function ApiData() 
	{
		$data = parent::ApiData();
		if ($data['Title'])
		{
			$data['name'] = substr($data['Title'],0,50);
		}
		elseif ($data['name'])
		{
			$data['name'] = substr($data['name'],0,50);
		}
		$data['parent_id'] = $data['parent_id'];
		$this->extend('updateApiData',$data);
		return $data;
	}
	
	public function Sync() 
	{
		$apiData = $this->ApiData();
		$apiClient = $this->ApiClient();
		if ( ($apiData['id']) )
		{
			$response = $apiClient->updateCategory($apiData['id'], new \BigCommerce\Api\v3\Model\CategoryPut($apiData));
		}
		else
		{
			$response = $apiClient->createCategory(new \BigCommerce\Api\v3\Model\CategoryPost($apiData));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public function matchName()
	{
		return preg_replace('/[^a-zA-Z0-9]/','',strtolower($this->name));
	}
	
	public static function getTopLevelCategories($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$allCategories = self::getCategories($refresh);
			$cachedData = $allCategories->Filter('parent_id',0);
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
//	protected static $_categoryTree;
//	public static function getCategoryTree()
//	{
//		if (is_null(self::$_categoryTree))
//		{
//			$inst = self::singleton();
//			$apiClient = $inst->ApiClient();
//			self::$_categoryTree = ArrayList::create();
//			foreach($apiClient->getCategoryTree()->getData() as $treeCat)
//			{
//				$newInst = Injector::inst()->create(static::class, []);
//				$newInst->loadApiData($treeCat);
//				self::$_categoryTree->push($newInst);
//			}
//		}
//		return self::$_categoryTree;
//	}
	
	public static function getById($id)
	{
		if ($id)
		{
			$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__.$id);
			$cachedData = self::fromCache($cacheName);
			if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
			{
				$inst = Injector::inst()->create(static::class, []);
				$apiClient = $inst->ApiClient();
				$apiResponse = $apiClient->getCategoryById($id);
				$inst->loadApiData($apiResponse->getData());
				$cachedData = $inst;
				self::toCache($cacheName, $inst);
			}
			return $cachedData;
		}
	}
	
	public static function getCategories($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			$apiCategoriesResponse = $apiClient->getCategories(['page' => $page, 'limit' => 100]);
			$responseMeta = $apiCategoriesResponse->getMeta();
			while(($apiCategories = $apiCategoriesResponse->getData()) && (count($apiCategories)))
			{
				foreach($apiCategories as $bcCategory)
				{
					$newInst = Injector::inst()->create(static::class, []);
					$newInst->loadApiData($bcCategory);
					$cachedData->push($newInst);
				}
				$page++;
				$apiCategoriesResponse = $apiClient->getCategories(['page' => $page, 'limit' => 100]);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function getCategoryTree($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$apiCategoriesResponse = $apiClient->getCategoryTree();
			$apiCategories = $apiCategoriesResponse->getData();
			foreach($apiCategories as $bcCategory)
			{
				$newInst = Injector::inst()->create(static::class, []);
				$newInst->loadApiData($bcCategory);
				$cachedData->push($newInst);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function forDropdown()
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = [];
			foreach(self::getCategories() as $bcCategory)
			{
				$cachedData[$bcCategory->id] = $bcCategory->name;
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function forNestedDropdown($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = [];
			foreach(self::getCategoryTree($refresh) as $cat)
			{
				$cat->addToNestedDropdown($cachedData, 0);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	protected function addToNestedDropdown(&$array, $level = 0)
	{
		$array[$this->id] = str_repeat(". ",(2 * $level)).$this->name;
		foreach($this->children as $child)
		{
			$child->addToNestedDropdown($array, $level + 1);
		}
	}
	
	protected $_children;
	public function Children($refresh = false)
	{
		if ( (is_null($this->_children)) || ($refresh) )
		{
			$cacheName = self::generateCacheKey(self::Config()->get('cache_name').'-'.$this->id.'-'.__FUNCTION__);
			$this->_children = self::fromCache($cacheName);
			if ( (!self::isCached($cacheName)) || (!$this->_children) || ($refresh) )
			{
				$allCategories = self::getCategories($refresh);
				$this->_children = $allCategories->Filter('parent_id',$this->id);
				self::toCache($cacheName, $this->_children);
			}
		}
		return $this->_children;
	}
}









