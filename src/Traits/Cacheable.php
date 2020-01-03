<?php

namespace IQnection\BigCommerceApp\Traits;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;

trait Cacheable
{
	use Configurable,
    	Injectable;
	
	private static $cache_namespace = 'bcCache';
	
	public static function generateCacheKey($arg1)
	{
		$args = func_get_args();
		return md5(json_encode($args));
	}
	
	public static function cacheInterface()
	{
		return Injector::inst()->get(CacheInterface::class . '.' . self::Config()->get('cache_namespace'));
	}
	
	public static function toCache($name, $data, $lifetime = 300)
	{
		return self::cacheInterface()->set($name, $data, $lifetime);
	}
	
	public static function fromCache($name)
	{
		return self::cacheInterface()->get($name);
	}
	
	public static function clearCache($name)
	{
		return self::cacheInterface()->delete($name);
	}
	
	public static function isCached($name)
	{
		return self::cacheInterface()->has($name);
	}
}

