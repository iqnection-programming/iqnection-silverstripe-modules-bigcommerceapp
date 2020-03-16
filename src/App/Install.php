<?php

namespace IQnection\BigCommerceApp\App;

use SilverStripe\Control\Controller;
use IQnection\BigCommerceApp\Client;
use SilverStripe\SiteConfig\SiteConfig;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BCLog;
use IQnection\BigCommerceApp\Model\Notification;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Forms;
use IQnection\BigCommerceApp\Model\Product;
use IQnection\BigCommerceApp\Model\Category;
use UncleCheese\Dropzone\FileAttachmentField;

class Install extends Controller
{
	private static $hidden = true;
	private static $install_url = 'https://login.bigcommerce.com/app/%s/install';
	private static $url_segment = '_bc/install';
	private static $install_post_back_url = 'https://login.bigcommerce.com/oauth2/token';
	
	private static $extensions = [
		\IQnection\BigCommerceApp\Extensions\DashboardTheme::class
	];
	
	private static $allowed_actions = [
		'ping',
		'index',
		'install',
		'installerror',
		'uninstall',
	];
	
	public function index()
	{
		return $this->install();
	}
	
	/**
	 * callback when installing the app to a BigCommerce store
	 */
	public function install()
	{
		BCLog::info('Initial Install', $this->getRequest()->requestVars());
		if ($installStatus = $this->getRequest()->getVar('external_install'))
		{
			return $this->confirmInstall($installStatus);
		}
		if (!$member = Security::getCurrentUser())
		{
			$message = 'Before you can install this app, you must open the SilverStripe admin in another tab and have an active login session. 
			Once this is ready, come back and initiate the install process again.';
			return Security::permissionFailure($this, $message);
			return $this->Customise(['Content' => $message])->renderWith(['IQnection/BigCommerceApp/App/NoAuth']);
		}
		$siteconfig = SiteConfig::current_site_config();
		$code = $this->getRequest()->getVar('code');
		$scope = $this->getRequest()->getVar('scope');
		$context = $this->getRequest()->getVar('context');
		
		$client = new \GuzzleHttp\Client();
		$postBack = [
			'client_id' => Client::Config()->get('client_id'),
			'client_secret' => Client::Config()->get('client_secret'),
			'code' => $code,
			'scope' => $scope,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $siteconfig->getBigCommerceAuthCallbackUrl(),
			'context' => $context
		];
		BCLog::info('Installing Postback', $postBack);
		$response = $client->request('POST', $this->Config()->get('install_post_back_url'), [
			'headers' => [
				'Content-Type' => 'application/json'
			],
			'json' => $postBack
		]);
		$responseData = json_decode((string) $response->getBody());
		BCLog::info('Install Postback Response', $responseData);
		if ($access_token = $responseData->access_token)
		{
			$member = Security::getCurrentUser();
			$member->BigCommerceID = $responseData->user->id;
			$member->write();

			$siteconfig->BigCommerceStoreHash = preg_replace('/.*?\/([a-zA-Z0-9_-]+)/','$1',$responseData->context);
			$siteconfig->BigCommerceApiAccessToken = $access_token;
			$siteconfig->BigCommerceApiScope = $responseData->scope;
			$siteconfig->write();
			return $this->Customise(['Content' => '<p>Install in progress, please wait...</p>']);
		}
		return $this->redirect($this->Link('installerror'));
	}
	
	public function installerror()
	{
		return $this->Customise(['Content' => '<p>There was a problem installing the app.</p>', 'HideNav' => true]);
	}
	
	/**
	 * Sends a callback to BigCommerce to let their server know if the install was successfull
	 */
	private function confirmInstall($installStatus)
	{
		$successUrl = sprintf('https://login.bigcommerce.com/app/%s/install/succeeded', Client::Config()->get('client_id'));
		$failUrl = sprintf('https://login.bigcommerce.com/app/%s/install/failed', Client::Config()->get('client_id'));
		$client = new \GuzzleHttp\Client();
		$callUrl = (empty($installStatus)) ? $failUrl : $successUrl;
		$response = $client->request($callUrl);
		if (empty($installStatus))
		{
			return $this->renderWith(['BigCommerceInstallError']);
		}
		return $this->renderWith(['BigCommerceInstallComplete']);
	}
	
	/**
	 * callback used when uninstalling the app from a BigCommerce store
	 */	
	public function uninstall()
	{
		$siteconfig = SiteConfig::current_site_congfig();
		$siteconfig->BigCommerceApiAccessToken = $access_token;
		$siteconfig->write();
		return $this->redirect($this->Link());
	}
}