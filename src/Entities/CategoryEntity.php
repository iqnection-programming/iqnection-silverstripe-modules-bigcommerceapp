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
//		$data['parent_id'] = $data['parent_id'];
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
			$allCategories = self::getAll($refresh);
			$cachedData = $allCategories->Filter('parent_id',0);
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}

	public static function getById($id, $params = [], $refresh = false)
	{
		if ($id)
		{
			$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__.$id);
			$cachedData = self::fromCache($cacheName);
			if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
			{
				$inst = Injector::inst()->create(static::class, []);
				$apiClient = $inst->ApiClient();
				$apiResponse = $apiClient->getCategoryById($id, $params);
				$inst->loadApiData($apiResponse->getData());
				$cachedData = $inst;
				self::toCache($cacheName, $inst);
			}
			return $cachedData;
		}
	}
	
	public static function getAll($refresh = false, $params = [])
	{
		$limited = (isset($params['page']) || isset($params['limit']));
		if (!isset($params['page']))
		{
			$params['page'] = 1;
		}
		if (!isset($params['limit']))
		{
			$params['limit'] = 100;
		}
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'),json_encode($params));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			
			$apiCategoriesResponse = $apiClient->getCategories($params);
			$responseMeta = $apiCategoriesResponse->getMeta();
			while(($apiCategories = $apiCategoriesResponse->getData()) && (count($apiCategories)))
			{
				foreach($apiCategories as $bcCategory)
				{
					$newInst = Injector::inst()->create(static::class, []);
					$newInst->loadApiData($bcCategory);
					$cachedData->push($newInst);
				}
				if ($limited)
				{
					break;
				}
				$params['page']++;
				$apiCategoriesResponse = $apiClient->getCategories($params);
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
			foreach(self::getAll() as $bcCategory)
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
				$allCategories = self::getAll($refresh);
				$this->_children = $allCategories->Filter('parent_id',$this->id);
				self::toCache($cacheName, $this->_children);
			}
		}
		return $this->_children;
	}
}









