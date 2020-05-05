<?php

namespace IQnection\BigCommerceApp\SiteConfig;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;
use IQnection\BigCommerceApp\App\Main;
use IQnection\BigCommerceApp\App\Auth;
use IQnection\BigCommerceApp\App\Install;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;

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
		$fields->findOrMakeTab('Root.Developer.BigCommerce.API');

		$fields->addFieldsToTab('Root.Developer.BigCommerce.API', [
			Forms\ReadonlyField::create('BigCommerceStoreHash','BigCommerce Store Hash'),
			Forms\ReadonlyField::create('BigCommerceApiAccessToken','BigCommerce Access Token'),
			Forms\TextField::create('BigCommerceStoreUrl','BigCommerce Store URL') 
		]);
		$fields->addFieldsToTab('Root.Developer.BigCommerce.App Configuration', [
			Forms\LiteralField::create('BgInstall','<div><p>Before you can install and connect to BigCommerce, you need to create an app and retrieve a Client ID</p></div>'),
			Forms\LiteralField::create('bcUrls','<p>When setting up your BigCommerce App, use the following URLs:</p>'),
			Forms\ReadonlyField::create('BigCommerceAuthCallbackUrl','Auth Callback URL')->setValue($this->getBigCommerceAuthCallbackUrl()),
			Forms\ReadonlyField::create('BigCommerceLoadCallbackUrl','Load Callback URL')->setValue($this->getBigCommerceLoadCallbackUrl()),
			Forms\ReadonlyField::create('BigCommerceUninstallCallbackUrl','Uninstall Callback URL')->setValue($this->getBigCommerceUninstallCallbackUrl()),
			Forms\LiteralField::create('bcUrlsAfter','<p>Once your app configuration is setup with values, set the client id and client secret in your site configuration files (see below), then install from the BigCommerce apps interface</p>'),
			Forms\LiteralField::create('bcUrlsConfigSetup',"<pre>IQnection\BigCommerceApp\Client:\n&nbsp;&nbsp;client_id: '[your client id]'\n&nbsp;&nbsp;client_secret: '[your client secret]'</pre>"),
		]);
		$fields->addFieldsToTab('Root.Developer.BigCommerce.Cron Jobs', [
			Forms\LiteralField::create('cronNote', '<p>The following cron jobs should be setup</p>'),
			Forms\ReadonlyField::create('cron-backgroundjobs','Background Jobs')
				->setValue('*/5 * * * * /usr/local/bin/php '.BASE_PATH.'/vendor/silverstripe/framework/cli-script.php dev/tasks/background-jobs >/dev/null 2>&1')
				->setDescription('Every 5 minutes'),
		]);
	}
	
	public function getBigCommerceInstallUrl()
	{
		if ($clientID = Client::Config()->get('client_id'))
		{
			return sprintf(Install::Config()->get('install_url'), $clientID);
		}
	}
	
	public function getBigCommerceAuthCallbackUrl()
	{
		return Injector::inst()->get(\IQnection\BigCommerceApp\App\Install::class)->AbsoluteLink('install');
	}
	
	public function getBigCommerceLoadCallbackUrl()
	{
		return Injector::inst()->get(\IQnection\BigCommerceApp\App\Auth::class)->AbsoluteLink('load');
	}
	
	public function getBigCommerceUninstallCallbackUrl()
	{
		return Injector::inst()->get(\IQnection\BigCommerceApp\App\Install::class)->AbsoluteLink('uninstall');
	}
	
	public function BigCommerceLink($action = null)
	{
		return Controller::join_links($this->owner->BigCommerceStoreUrl, $action);
	}
}















