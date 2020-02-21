<?php

namespace IQnection\BigCommerceApp\App;

use SilverStripe\Security\Security;
use SilverStripe\Security\Authenticator;
use SilverStripe\Control\Controller;

class Auth extends Security
{
	private static $allowed_actions = [
		'login',
		'logout'
	];
	
	private static $extensions = [
		\IQnection\BigCommerceApp\Extensions\DashboardTheme::class
	];
	
	private static $url_segment = '_bc/auth';
	
	private static $autologin_enabled = false;
	
	private static $template_main = 'IQnection\BigCommerceApp\App\Auth';
	
	private static $page_class = \IQnection\BigCommmerceApp\App\Main::class;
	
	private static $login_url = '_bc/auth/login';
	
	private static $logout_url = '_bc/auth/logout';
	
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
	
	public function Form()
	{
		$form = parent::Form();
if ($_SERVER['REMOTE_ADDR'] == '72.94.51.229'){ print "<pre>\nFile: ".__FILE__."\nLine: ".__LINE__."\nOutput: \n"; print_r($form); print '</pre>'; die(); }
		$this->BootstrapForm($form);
		return $form;
	}
}