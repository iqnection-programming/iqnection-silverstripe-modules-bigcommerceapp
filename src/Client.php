<?php

namespace IQnection\BigCommerceApp;

use BigCommerce\Api\v3\Configuration as BcConfig;
use BigCommerce\Api\v3\ApiClient as BcClient;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use IQnection\BigCommerceApp\Traits\Logable;

class Client
{
	use Configurable, Extensible, Injectable;
	
	private static $api_url;
	private static $client_id;
	private static $client_secret;
	private static $debug = false;
	
	protected static $_inst;
	protected static $_bc_apis = [];	
	protected $_api_config;
	protected $_api_client;
	
	/**
	 * Builds the config object for the current client
	 * This method is required in case the configuration 
	 * needs to be changed in mid-execution
	 * This method will be specific to the client class
	 */
	public function ApiConfig() {}
	
	/*
	 * Builds and provides the API client for the particular
	 * API version
	 */
	public function ApiClient() {}
	
	public static function inst()
	{
		if (is_null(static::$_inst))
		{
			static::$_inst = Injector::inst()->get(static::class);
		}
		return static::$_inst;
	}
	
	protected function getApiUrl()
	{
		if ($hash = SiteConfig::current_site_config()->BigCommerceStoreHash)
		{
			return sprintf($this->Config()->get('api_url'), $hash);
		}
	}
	
	/**
	 * Shortcut method to retrieve a specific API class
	 */
	public function Api($key, $className = null)
	{
		if (class_exists($key))
		{
			$className = $key;
			$key = md5($className);
		}
		$key = strtolower($key);
		if (!isset(self::$_bc_apis[$key]))
		{
			if (!class_exists($className))
			{
				throw new \Exception('Class '.$className.' Not Found');
			}
			self::$_bc_apis[$key] = new $className($this->ApiClient());
		}
		return self::$_bc_apis[$key];
	}
}








