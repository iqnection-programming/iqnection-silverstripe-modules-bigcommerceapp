<?php

namespace IQnection\BigCommerceApp;

use Bigcommerce\Api\Client as Bigcommerce;
use SilverStripe\SiteConfig\SiteConfig;

class ClientV2 extends Client
{
	private static $api_url = 'https://api.bigcommerce.com/stores/%s/v2';
	
	public function ApiConfig()
	{
		if (is_null($this->_api_config))
		{
			Bigcommerce::configure([
				'client_id' => $this->Config()->get('client_id'),
				'auth_token' => SiteConfig::current_site_config()->BigCommerceApiAccessToken,
				'store_hash' => SiteConfig::current_site_config()->BigCommerceStoreHash
			]);
		}
		return $this;
	}
	
	public function ApiClient()
	{
		return $this->ApiConfig();
	}
	
	/**
	 * This allows us to call methods on an object instance
	 * while still calling the method staically on the BigCommerce class
	 */
	public function __call($method, $args)
	{
		// default to object/class methods
		if ($this->hasMethod($method))
		{
			return call_user_func_array([$this, $method], $args);
		}
		
		// assume the method is a BigCommerce API static method
		return call_user_func_array([BigCommerce::class, $method], $args);
	}
}








