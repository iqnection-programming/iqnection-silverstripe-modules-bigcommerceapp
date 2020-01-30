<?php

namespace IQnection\BigCommerceApp\SiteConfig;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;
use IQnection\BigCommerceApp\App\Main;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class SiteConfig extends DataExtension
{
	private static $db = [
		'BigCommerceStoreUrl' => 'Varchar(255)',
		'BigCommerceApiUrl' => 'Varchar(255)',
		'BigCommerceStoreHash' => 'Varchar(20)',
		'BigCommerceApiAccessToken' => 'Varchar(255)',
		'BigCommerceApiScope' => 'Text',
	];
	
	public function updateCMSFields(Forms\FieldList $fields)
	{
		$fields->removeByName([
			'BigCommerceApiAccessToken'
		]);
		$fields->findOrMakeTab('Root.Developer.BigCommerce');

		$fields->addFieldsToTab('Root.Developer.BigCommerce', [
			Forms\ReadonlyField::create('BigCommerceStoreHash','BigCommerce Store Hash'),
			Forms\ReadonlyField::create('BigCommerceApiAccessToken','BigCommerce Access Token'),
			Forms\TextField::create('BigCommerceStoreUrl','BigCommerce Store URL') 
		]);
		//if ($url = $this->getBigCommerceInstallUrl())
//		{
//			$linkTitle =  ( ($this->owner->BigCommerceStoreHash) && ($this->owner->BigCommerceApiAccessToken) ) ? 'Reinstall App' : 'Install the BigCommerce App';
//			$fields->addFieldToTab('Root.Developer.BigCommerce', Forms\LiteralField::create('BgInstall','<div><p><a href="'.$url.'" class="btn btn-primary" target="_blank">'.$linkTitle.'</a></p></div>') );
//		}
//		else
		{
			$fields->addFieldToTab('Root.Developer.BigCommerce', Forms\LiteralField::create('BgInstall','<div><p>Before you can install and connect to BigCommerce, you need to create an app and retrieve a Client ID</p></div>') );
			$fields->addFieldToTab('Root.Developer.BigCommerce', Forms\LiteralField::create('bcUrls','<div><p>When setting up your BigCommerce App, use the following URLs:<br />
Auth Callback URL: '.$this->getBigCommerceAuthCallbackUrl().'<br />
Load Callback URL: '.$this->getBigCommerceLoadCallbackUrl().'<br />
Uninstall Callback URL: '.$this->getBigCommerceUninstallCallbackUrl().'</p>
<p>Once your app configuration is setup with values, set the client id in the site configuration files and install from teh BigCommerce apps interface<p></div>') );
		}

	}
	
	public function getBigCommerceInstallUrl()
	{
		if ($clientID = Client::Config()->get('client_id'))
		{
			return sprintf(Main::Config()->get('install_url'), $clientID);
		}
	}
	
	public function getBigCommerceAuthCallbackUrl()
	{
		return Injector::inst()->get(\IQnection\BigCommerceApp\App\Main::class)->AbsoluteLink('install');
	}
	
	public function getBigCommerceLoadCallbackUrl()
	{
		return Injector::inst()->get(\IQnection\BigCommerceApp\App\Main::class)->AbsoluteLink('load');
	}
	
	public function getBigCommerceUninstallCallbackUrl()
	{
		return Injector::inst()->get(\IQnection\BigCommerceApp\App\Main::class)->AbsoluteLink('uninstall');
	}
}















