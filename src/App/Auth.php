<?php

namespace IQnection\BigCommerceApp\App;

use SilverStripe\Security\Security;
use SilverStripe\Security\Authenticator;
use SilverStripe\Control\Controller;
use IQnection\BigCommerceApp\Client;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BCLog;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;

class Auth extends Security
{
	private static $allowed_actions = [
		'login',
		'load',
		'logout'
	];
	
	private static $extensions = [
		\IQnection\BigCommerceApp\Extensions\DashboardTheme::class
	];
	
	private static $url_segment = '_bc/auth';
	
	private static $autologin_enabled = false;
	
	private static $frame_options = false;
	
	private static $template_main = 'IQnection\BigCommerceApp\App\Auth';
	
	private static $page_class = \IQnection\BigCommmerceApp\App\Main::class;
	
	private static $login_url = '_bc/auth/login';
	
	private static $logout_url = '_bc/auth/logout';
	
	private static $default_user_account;
	
	public function Link($action = null)
	{
		return \SilverStripe\Control\Controller::join_links('/',$this->owner->Config()->get('url_segment'),$action);
	}
	
	public function AbsoluteLink($action = null)
	{
		return preg_replace('/^http\:/','https:',\SilverStripe\Control\Director::absoluteURL($this->owner->Link($action)));
	}
	
	public static function permissionFailure($controller = null, $messageSet = null)
	{
		return $controller->redirect(Controller::join_links(
			self::config()->uninherited('login_url'),
			"?BackURL=" . urlencode($_SERVER['REQUEST_URI'])
		));
	}
		
	public function DashboardLoginForm()
	{
		return $this->Form();
	}
	
	/**
	 * action used to load the App content into the iframe within the BigCommerce admin app interface
	 */
	public function load()
	{
		BCLog::info('App Load', $data);
		$signed_payload = $this->getRequest()->getVar('signed_payload');
		if (!$data = $this->verifyBcSignedRequest($signed_payload))
		{
			BCLog::info('Invalid Store', $data);
			$this->addAlert('Invalid','warning');
			return $this->Customise(['Error' => true, 'Content' => 'Invalid Store', 'ErrorData' => print_r([
				'Payload' => $signed_payload,
				'Payload Data' => $data
			],1)]);
		}
		if (!$member = $this->validateAccess($data))
		{
			BCLog::info('Invalid Access', $data);
			$this->addAlert('Your account has not been activated yet','warning');
			return $this->Customise(['Content' => 'Your account has not been activated yet', 'ErrorData' => print_r([
				'Email' => $data['user']['email']
			],1)]);
		}
		$identityStore = Injector::inst()->get(IdentityStore::class);
		$identityStore->logOut($this->getRequest());
        	$identityStore->logIn($member, true, $this->getRequest());
		$this->addAlert('Logged Successfully');
		BCLog::info('Logged Successfully', $data);
		return $this->redirect(Injector::inst()->get(Main::class)->AbsoluteLink('?sess='.$member->TempIDHash));
	}
	
	/**
	 * verifies the request is a valid BigCommerce request to load the app
	 */
	private function verifyBcSignedRequest($signedRequest) 
	{
		list($encodedData, $encodedSignature) = explode('.', $signedRequest, 2);
	
		// decode the data
		$signature = base64_decode($encodedSignature);
		$jsonStr = base64_decode($encodedData);
		$data = json_decode($jsonStr, true);
	
		// confirm the signature
		$expectedSignature = hash_hmac('sha256', $jsonStr, Client::Config()->get('client_secret'), $raw = false);
		if (!hash_equals($expectedSignature, $signature)) 
		{
			error_log('Bad signed request from BigCommerce!');
			return null;
		}
		return $data;
	}
	
	private function validateAccess($payload) 
	{
		$SiteConfig = SiteConfig::current_site_config();
		if (is_string($payload))
		{
			$payload = json_decode($payload,1);
		}
		if ($SiteConfig->BigCommerceStoreHash == $payload['store_hash'])
		{
			$bgID = $payload['user']['id'];
			if ($member = Member::get()->Find('BigCommerceID',$bgID))
			{
				return $member;
			}
			$userEmail = $payload['user']['email'];
			if ($member = Member::get()->Find('Email',$userEmail))
			{
				$member->BigCommerceID = $bgID;
				$member->write();
				return $member;
			}
			if ($member = Security::getCurrentUser())
			{
				return $member;
			}
			if ($default_username = $this->Config()->get('default_user_account'))
			{
				return Member::get()->Find('Email',$default_username);
			}
		}
		return false;
	}
}