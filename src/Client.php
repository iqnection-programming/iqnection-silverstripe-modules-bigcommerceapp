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
	
	private static $api_url = 'https://api.bigcommerce.com/stores/%s/v3';
	private static $client_id;
	private static $client_secret;
	private static $debug = false;
	
	protected static $_inst;
	protected $_bc_apis = [];	
	protected $_api_config;
	protected $_api_client;
	
	public static function inst()
	{
		if (is_null(self::$_inst))
		{
			self::$_inst = Injector::inst()->get(Client::class);
		}
		return self::$_inst;
	}
	
	public function ApiConfig()
	{
		if (is_null($this->_api_config))
		{
			$this->_api_config = new BcConfig();
			$this->_api_config->setHost($this->getApiUrl());
			$this->_api_config->setClientId($this->Config()->get('client_id'));
			$this->_api_config->setAccessToken(SiteConfig::current_site_config()->BigCommerceApiAccessToken);
			if ($this->Config()->get('debug'))
			{
				$this->setDebug(true);
			}
		}
		return $this->_api_config;
	}
	
	public function setDebug($on = true)
	{
		$this->_api_config->setDebug($on);
		if ($on) { $this->_api_config->setDebugFile(BASE_PATH.'/bc-debug.log'); }
		return $this;
	}
	
	public function ApiClient()
	{
		if (is_null($this->_api_client))
		{
			$this->_api_client = new BcClient($this->ApiConfig());
		}
		return $this->_api_client;
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
		if (!isset($this->_bc_apis[$key]))
		{
			if (!class_exists($className))
			{
				throw new \Exception('Class '.$className.' Not Found');
			}
			$this->_bc_apis[$key] = new $className($this->ApiClient());
		}
		return $this->_bc_apis[$key];
	}
}








