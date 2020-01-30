<?php

namespace IQnection\BigCommerceApp\App;

use SilverStripe\Control\Controller;
use IQnection\BigCommerceApp\Client;
use SilverStripe\SiteConfig\SiteConfig;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BCLog;
use IQnection\BigCommerceApp\Model\Notification;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\PaginatedList;

class Main extends Controller
{
	private static $install_url = 'https://login.bigcommerce.com/app/%s/install';
	private static $url_segment = '_bc';
	private static $install_post_back_url = 'https://login.bigcommerce.com/oauth2/token';
	private static $theme_name = 'bigcommerceapp';
	
	private static $allowed_actions = [
		'index',
		'install',
		'installerror',
		'load',
		'uninstall',
		'search_api',
		'_test' => 'ADMIN'
	];
	
	private static $url_handlers = [
		'notification//$subAction!/$ID!' => 'updateNotification'
	];

	private static $apps = [
		'Main' => Main::class,
		'Categories' => Categories::class,
		'Widgets' => Widgets::class,
		'SilverStripe' => SSAdminRedirect::class,
		'Logs' => AppLogs::class
	];
	
	private static $nav_links = [
		'Home' => [
			'path' => '',
			'icon' => 'home'
		]
	];
	
	private static $theme_packages = [
		'base',
	];
	
	protected $package_includes = [
		'base' => [
			'css' => [
				"assets/vendor/bootstrap/css/bootstrap.min.css",
				"assets/vendor/fonts/circular-std/style.css",
				"assets/libs/css/style.css",
				"assets/vendor/fonts/fontawesome/css/fontawesome-all.css",
				"assets/vendor/select2/css/select2.css",
				"css/app.scss"
			],
			'js' => [
				"assets/vendor/jquery/jquery-3.3.1.min.js",
				"assets/vendor/bootstrap/js/bootstrap.bundle.js",
				"assets/vendor/slimscroll/jquery.slimscroll.js",
				"assets/libs/js/main-js.js",
				"assets/vendor/select2/js/select2.min.js",
				"javascript/app.js"
			]
		],
		'datatables' => [
			'css' => [
				"assets/vendor/datatables/css/dataTables.bootstrap4.css",
				"assets/vendor/datatables/css/buttons.bootstrap4.css",
				"assets/vendor/datatables/css/select.bootstrap4.css",
				"assets/vendor/datatables/css/fixedHeader.bootstrap4.css"
			],
			'js' => [
				"assets/vendor/datatables/js/dataTables.bootstrap4.min.js",
				"assets/vendor/datatables/js/buttons.bootstrap4.min.js",
				"assets/vendor/datatables/js/data-table.js",
				"https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js",
				"https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js",
				"https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js",
				"https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js",
				"https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js",
				"https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js",
				"https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js",
				"https://cdn.datatables.net/buttons/1.5.2/js/buttons.colVis.min.js",
				"https://cdn.datatables.net/rowgroup/1.0.4/js/dataTables.rowGroup.min.js",
				"https://cdn.datatables.net/select/1.2.7/js/dataTables.select.min.js",
				"https://cdn.datatables.net/fixedheader/3.1.5/js/dataTables.fixedHeader.min.js"
			]
		],
		'forms' => [
			'css' => [],
			'js' => [
				"assets/vendor/inputmask/js/jquery.inputmask.bundle.js",
				"assets/vendor/jquery/jquery-3.3.1.min.js",
				"assets/vendor/bootstrap/js/bootstrap.bundle.js",
				"assets/vendor/slimscroll/jquery.slimscroll.js",
				"assets/vendor/parsley/parsley.js",
				"assets/libs/js/main-js.js",
			]
		]
	];
	
	protected static $_includedCss = [];
	protected function combineCssFiles($css)
	{
		$themeName = $this->Config()->get('theme_name');
		$CssFiles = [];
		foreach($css as $cssFile)
		{
			$cssFile = preg_replace('/^\//','',$cssFile);
			if (in_array($cssFile, self::$_includedCss))
			{
				continue;
			}
			self::$_includedCss[] = $cssFile;
			if (preg_match('/^http/',$cssFile))
			{
				Requirements::css($cssFile);
				continue;
			}
			$cssFile = preg_replace('/\.css|\.scss/','',$cssFile);
			// searching this way will favor a .scss file over .css
			foreach(['.css','.scss'] as $ext)
			{
				if ($CssFilePath = ThemeResourceLoader::inst()->findThemedResource($cssFile.$ext,array($themeName)))
				{
					$CssFiles[$cssFile] = $CssFilePath;
				}
				elseif ($CssFilePath = ThemeResourceLoader::inst()->findThemedResource('css/'.$cssFile.$ext,array($themeName)))
				{
					$CssFiles[$cssFile] = $CssFilePath;
				}
			}
		}
		if (count($CssFiles))
		{
			Requirements::combine_files('dashboard-'.md5(json_encode($CssFiles)).'.css', $CssFiles);
		}
	}
	
	protected static $_includedJs = [];
	protected function combineJsFiles($js)
	{
		$themeName = $this->Config()->get('theme_name');
		$JsFiles = array();
		foreach($js as $jsFile)
		{
			$jsFile = preg_replace('/^\//','',$jsFile);
			if (in_array($jsFile, self::$_includedJs))
			{
				continue;
			}
			self::$_includedJs[] = $jsFile;
			if (preg_match('/^http/',$jsFile))
			{
				Requirements::javascript($jsFile);
				continue;
			}
			if ($JsFilePath = ThemeResourceLoader::inst()->findThemedJavascript($jsFile,array($themeName)))
			{
				$JsFiles[$JsFilePath] = $JsFilePath;
			}
		}

		if (count($JsFiles))
		{
			Requirements::combine_files('dashboard-'.md5(json_encode($JsFiles)).'.js', $JsFiles);	
		}
	}
	
	protected function loadThemePackage($packageName)
	{
		if (isset($this->package_includes[$packageName]))
		{
			if (isset($this->package_includes[$packageName]['css']))
			{
				$this->combineCssFiles($this->package_includes[$packageName]['css']);
			}
			if (isset($this->package_includes[$packageName]['js']))
			{
				$this->combineJsFiles($this->package_includes[$packageName]['js']);
			}
		}
	}

	protected function loadThemePackages()
	{
		foreach($this->Config()->get('theme_packages') as $packageName)
		{
			$this->loadThemePackage($packageName);
		}
	}
		
	public function _test()
	{
		
	}
	
	public function init()
	{
		parent::init();
		if (!$this->Config()->get('url_segment',Config::UNINHERITED))
		{
			user_error(get_class($this)." doesn't have a URL segment declared");
		}
		Requirements::customScript('window._search_url = "'.$this->Link('search_api').'";');
		Requirements::javascript('silverstripe/admin:thirdparty/tinymce/tinymce.min.js');
		Requirements::css('silverstripe/admin:client/dist/styles/editor.css');
		Requirements::customScript(
<<<JS
tinymce.init({
        selector: 'textarea.htmleditor',
        skin: 'silverstripe',
        max_height: 250,
        menubar: false
});
JS
		);
		$this->setDashboardTheme();
		$this->loadRequirements();
	}
	
	public function search_api()
	{
		$result = [];
		if ($call = $this->getRequest()->requestVar('call'))
		{
			list($className, $method) = explode('|',$call);
			$inst = Injector::inst()->get($className);
			$results = $inst->$method($this->getRequest());
		}
		return $this->getResponse()
			->addHeader('Content-Type','application/json')
			->setBody(json_encode(['results' => $results]));
	}
	
	public function Title()
	{
		$nav = $this->Config()->get('nav_links', Config::UNINHERITED);
		return key($nav);
	}
	
	public function Dashboard()
	{
    	$appClass = Main::class;
    	$apps = $this->Config()->get('apps');
		if (isset($apps[$app]))
		{
		  $appClass = $apps[$app];
		}
		return Injector::inst()->get($appClass);
	}
	
	public function index()
	{
		$notifications = ArrayList::create();
		if ($member = Security::getCurrentUser())
		{
			$notifications = $member->Notifications();
		}
		return $this->Customise([
			'ActiveNotifications' => PaginatedList::create($notifications->Filter('Status',Notification::STATUS_NEW), $this->getRequest())
				->setPageLength(20)
				->setPaginationGetVar('newStart'),
			'ViewedNotifications' => PaginatedList::create($notifications->Filter('Status',Notification::STATUS_VIEWED), $this->getRequest())
				->setPageLength(20)
				->setPaginationGetVar('viewedStart')
		]);
	}
	
	public function BootstrapForm(&$form)
	{
		$this->loadThemePackage('forms');
		foreach($form->Fields()->saveableFields() as $field)
		{
			$field->addExtraClass('mt-2 form-control');
			if ( ($field instanceof Forms\CheckboxField) ||
				($field instanceof Forms\CheckboxSetField) ||
				($field instanceof Forms\OptionSetField) )
			{
				$field->addExtraClass('w-auto d-inline-block');
			}
		}
		foreach($form->Fields() as $field)
		{
			if ($field instanceof Forms\CompositeField)
			{
				$field->addExtraClass('border p-3 mb-4');
			}
		}
		foreach($form->Actions() as $action)
		{
			$action->addExtraClass('btn mt-2 mr-2');
		}
	}
	
	protected function loadRequirements()
	{
		$themeName = $this->Config()->get('theme_name');
		Requirements::set_combined_files_folder('combined/'.$themeName);
		$this->loadThemePackages();
		$this->extend('updateRequirements');
	}
	
	protected function setDashboardTheme()
	{
		$baseThemes = SSViewer::get_themes();
		array_shift($baseThemes);
		// Put the theme at the top of the list
		array_unshift($baseThemes, $this->Config()->get('theme_name'));
		SSViewer::set_themes(array_unique($baseThemes));
		$this->extend('updateDashboardTheme');
	}
		
	public function Link($action = null)
	{
		return \SilverStripe\Control\Controller::join_links('/',$this->Config()->get('url_segment'),$action);
	}
	
	public function AbsoluteLink($action = null)
	{
		return \SilverStripe\Control\Director::absoluteURL($this->Link($action));
	}
	
	public function NavLinks()
	{
		if (!$links = $this->Config()->get('nav_links',Config::UNINHERITED))
		{
			user_error(get_class($this)." does't have any navigation specified");
		}
		$this->extend('updateNavLinks',$links);
		return $links;
	}
	
	protected function BuildNavChildren($children, $app)
	{
		$controller = Controller::curr();
		$action = $controller->getRequest()->param('Action');
		$links = ArrayList::create();
		foreach($children as $title => $details)
		{
			$path = trim((isset($details['path'])) ? $details['path'] : '#');
			if ($active = (get_class($controller) == $app))
			{
				if ($path != $action)
				{
					$active = ( ($path == '#') && (empty($action)) );
				}
			}
	
			$links->push(ArrayData::create([
				'Title' => $title,
				'ID' => md5(json_encode($details)),
				'Link' => $app->Link($path),
				'Icon' => (isset($details['icon'])) ? $details['icon'] : 'hockey-puck',
				'Children' => ((isset($details['children']))&&(is_array($details['children']))) ? $this->BuildNavChildren($details['children'],$app) : ArrayList::create(),
				'Active' => $active,
				'Target' => (isset($details['target'])) ? $details['target'] : false,
			]));
		}
		return $links;
	}
	
	public function Menu()
	{
		$links = ArrayList::create();
		foreach($this->Config()->get('apps') as $app)
		{
			$singleton = Injector::inst()->get($app);
			foreach($singleton->NavLinks() as $title => $details)
			{
				$details = (!is_array($details)) ? [$details] : $details;
				$links->push(ArrayData::create([
					'Title' => $title,
					'ID' => md5(json_encode($details)),
					'Link' => (array_key_exists('path',$details)) ? $singleton->Link($details['path']) : '#',
					'Icon' => (array_key_exists('icon',$details)) ? $details['icon'] : 'hockey-puck',
					'Children' => ((isset($details['children']))&&(is_array($details['children']))) ? $this->BuildNavChildren($details['children'],$singleton) : ArrayList::create(),
          			'Active' => (get_class(Controller::curr()) == $app),
          			'Target' => (isset($details['target'])) ? $details['target'] : false,
				]));
			}
		}
		$this->extend('updateMenu',$links);
		return $links;
	}
	
	public function updateNotification()
	{
		$status = $this->getRequest()->param('subAction');
		$notification = Security::getCurrentUser()->Notifications()->byID($this->getRequest()->param('ID'));
		if ( ($status) && ($notification) )
		{
			switch($status)
			{
				case 'd':
					$notification->Status = Notification::STATUS_DISMISSED;
					break;
				case 'v':
					$notification->Status = Notification::STATUS_VIEWED;
					break;
			}
			$notification->write();
		}
		return $this->redirectBack();
	}
	
	public function login()
	{
		user_error(__FUNCTION__.' not built yet');
	}
	
	public function logout()
	{
		Security::setCurrentUser(null);
		return $this->redirect($this->Link('login'));
	}
	
	protected $_Alerts = [];
	public function setAlerts($alerts)
	{
		$this->getRequest()->getSession()->set('alerts',$alerts);
		return $this;
	}
	
	public function addAlert($message, $status = 'success')
	{
		$this->_Alerts[] = [
			'Message' => $message,
			'Status' => $status
		];
		$this->setAlerts($this->_Alerts);
		return $this;
	}
	
	protected $_AlertsOut;
	public function Alerts()
	{
		if ( (is_null($this->_AlertsOut)) && (is_array($this->getRequest()->getSession()->get('alerts'))) )
		{
			$this->_AlertsOut = ArrayList::create();
			foreach($this->getRequest()->getSession()->get('alerts') as $alert)
			{
				$this->_AlertsOut->push(ArrayData::create($alert));
			}
			$this->setAlerts(false);
		}
		return $this->_AlertsOut;
	}
	
	protected function ajax_response($data,$success = true, $errors = [], $message = null)
	{
		if (!$this->getRequest()->isAjax())
		{
			$this->addAlert($message, ($success ? 'success' : 'danger'));
			return $this->redirectBack();
		}
		$response = [
			'data' => $data,
			'success' => (bool) $success,
			'errors' => $errors,
			'message' => $message
		];
		return $this->getResponse()
			->addHeader('Content-Type','application/json')
			->setBody(json_encode($response));
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
			return $this;
		}
		return $this->redirect($this->Link('installerror'));
	}
	
	public function installerror()
	{
		return $this->Customise(['HideNav' => true]);
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
	
	/**
	 * action used to load the App content into the iframe within the BigCommerce admin app interface
	 */
	public function load()
	{
		$signed_payload = $this->getRequest()->getVar('signed_payload');
		if (!$data = $this->verifyBcSignedRequest($signed_payload))
		{
			return $this->Customise(['Error' => true, 'ErrorMessage' => 'Invalid Store', 'ErrorData' => print_r([
				'Payload' => $signed_payload,
				'Payload Data' => $data
			],1)]);
		}
		BCLog::info('Loading App', $data);
		if (!$member = $this->validateAccess($data))
		{
			return $this->Customise(['HideNav' => true, 'Error' => true, 'ErrorMessage' => 'Your account has not been activated yet', 'ErrorData' => print_r([
				'Email' => $data['user']['email']
			],1)]);
		}
		Security::setCurrentUser($member);
		return $this->redirect($this->Link());
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
			$userEmail = $payload['user']['email'];
			$bgID = $payload['user']['id'];
			if ($member = Member::get()->Find('BigCommerceID',$bgID))
			{
				return $member;
			}
			if ($member = Member::get()->Find('Email',$userEmail))
			{
				$member->BigCommerceID = $bgID;
				$member->write();
				return $member;
			}
		}
		return false;
	}
}