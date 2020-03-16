<?php

namespace IQnection\BigCommerceApp;

use BigCommerce\Api\v3\Configuration as BcConfig;
use BigCommerce\Api\v3\ApiClient as BcClient;
use SilverStripe\SiteConfig\SiteConfig;

class ClientV3 extends Client
{
	private static $api_url = 'https://api.bigcommerce.com/stores/%s/v3';
	
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
	
	public function ApiClient()
	{
		if (is_null($this->_api_client))
		{
			$this->_api_client = new BcClient($this->ApiConfig());
		}
		return $this->_api_client;
	}
	
	public function setDebug($on = true)
	{
		$this->_api_config->setDebug($on);
		if ($on) { $this->_api_config->setDebugFile(BASE_PATH.'/bc-debug.log'); }
		return $this;
	}
}








