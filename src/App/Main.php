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
use SilverStripe\Forms;
use IQnection\BigCommerceApp\Model\Product;
use IQnection\BigCommerceApp\Model\Category;
use UncleCheese\Dropzone\FileAttachmentField;

class Main extends Controller
{
	const SKIP_SYNC_SESSION_VAR = 'skip-next-sync';
	
	private static $install_url = 'https://login.bigcommerce.com/app/%s/install';
	private static $url_segment = '_bc';
	private static $install_post_back_url = 'https://login.bigcommerce.com/oauth2/token';
	private static $theme_name = 'bigcommerceapp';
	private static $managed_class;
	
	private static $allowed_actions = [
		'index',
		'install',
		'installerror',
		'load',
		'uninstall',
		'search_api',
		'recordForm',
		'relation',
		'relatedObjectForm',
		'relationremove',
		'edit',
		'sort_items',
		'_test' => 'ADMIN'
	];
	
	private static $url_handlers = [
		'notification//$subAction!/$ID!' => 'updateNotification',
		'edit/$ID/relationremove/$ComponentName!/$RelatedID' => 'relationremove',
		'edit/$ID/relation/$ComponentName!/$RelatedID' => 'relation'
	];

	private static $apps = [
		'Main' => Main::class,
		'Products' => Products::class,
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
//				"assets/vendor/select2/css/select2.css",
				"https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css",
				"css/app.scss"
			],
			'js' => [
//				"assets/vendor/jquery/jquery-3.3.1.min.js",
				"assets/vendor/bootstrap/js/bootstrap.bundle.js",
				"assets/vendor/slimscroll/jquery.slimscroll.js",
				"assets/libs/js/main-js.js",
//				"assets/vendor/select2/js/select2.min.js",
				"https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js",
				"assets/vendor/shortable-nestable/Sortable.min.js",
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
//				"assets/vendor/datatables/js/dataTables.bootstrap4.min.js",
//				"assets/vendor/datatables/js/buttons.bootstrap4.min.js",
				"assets/vendor/datatables/js/data-table.js",
//				"https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js",
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
//				"assets/vendor/jquery/jquery-3.3.1.min.js",
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
		Requirements::customScript('window._search_url = "'.$this->AbsoluteLink('search_api').'";
window._sort_url = "'.$this->AbsoluteLink('sort_items').'";');
		Requirements::javascript("https://code.jquery.com/jquery-3.4.1.min.js");
		Requirements::javascript('silverstripe/admin:thirdparty/tinymce/tinymce.min.js');
		Requirements::css('silverstripe/admin:client/dist/styles/editor.css');
		Requirements::customScript(
<<<JS
$('[data-editor="tinyMCE"]').each(function(){
	var config = $(this).data('config');
	config.selector = '#'+$(this).attr('id');
	tinymce.init(config);
});
JS
		);
		$this->setDashboardTheme();
		$this->loadRequirements();
	}
	
	/**
	 * common method/action for searching resources
	 * expects params to pass as follows:
	 * @param array|object $search = [value => {your search term}, order? => [ [column => {db column}, dir => {sort direction}] ]]
	 * @param string $resource = categories|category, products|product
	 * @returns object DataList of search results. If Ajax call, results are out directly from child method
	 */
	public function search_api($search = null, $resource = null)
	{
		$search = (is_string($search)) ? $search : $this->getRequest()->requestVar('search');
		$resource = (is_string($resource)) ? $resource : $this->getRequest()->requestVar('resource');
		switch(strtolower($resource))
		{
			case 'categories':
			case 'category':
			{
				return $this->searchCategories($search);
				break;
			}
			default:
			case 'products':
			case 'product':
			{
				return $this->searchProducts($search);
				break;
			}
		}
	}
	
	public function searchCategories($search)
	{
		$records = Category::get();
		$recordsTotal = $records->Count();

		if (trim($search['value']))
		{
			$records = $records->FilterAny([
				'Title:PartialMatch' => trim($search['value']),
			]);
		}
		if ($orders = $this->getRequest()->requestVar('order'))
		{
			$cols = ['ID','BigID','Title','Created'];
			foreach($orders as $order)
			{
				$col = $cols[$order['column']];
				$dir = $order['dir'];
				$records = $records->Sort($col,$dir);
			}
		}
		
		$finalRecordsTotal = $records->Count();
		$limit = $this->getRequest()->requestVar('length') ? $this->getRequest()->requestVar('length') : 100;
		$start = 0;
		if ($this->getRequest()->requestVar('start'))
		{
			$start = $this->getRequest()->requestVar('start');
		}
		$records = $records->Limit($limit,$start);
		
		
		if ($this->getRequest()->isAjax())
		{
			$ajaxData = [
				'data' => [],
				'draw' => strtotime('now'),
				'recordsTotal' => $recordsTotal,
				'recordsFiltered' => $finalRecordsTotal,
			];
			foreach($records as $record)
			{
				$ajaxData['data'][] = [
					'ID' => $record->ID,
					'BigID' => $record->BigID,
					'Title' => $record->Title,
					'Breadcrumbs' => $record->Breadcrumbs(),
					'Created' => $record->dbObject('Created')->Nice(),
					'DropdownText' => $record->Breadcrumbs()
				];
			}
			header('Content-Type: application/json');
			print json_encode($ajaxData);
			die();
		}
			
		return $this->Customise([
			'Categories' => $records
		]);
	}
	
	public function searchProducts($search)
	{
		$products = Product::get();
		$recordsTotal = $products->Count();

		if (trim($search['value']))
		{
			$products = $products->FilterAny([
				'sku:PartialMatch' => trim($search['value']),
				'Title:PartialMatch' => trim($search['value']),
			]);
		}
		if ($orders = $this->getRequest()->requestVar('order'))
		{
			$cols = ['ID','BigID','Title','SKU','Created'];
			foreach($orders as $order)
			{
				$col = $cols[$order['column']];
				$dir = $order['dir'];
				$products = $products->Sort($col,$dir);
			}
		}
		
		$finalProductsTotal = $products->Count();
		$limit = $this->getRequest()->requestVar('length') ? $this->getRequest()->requestVar('length') : 100;
		$start = 0;
		if ($this->getRequest()->requestVar('start'))
		{
			$start = $this->getRequest()->requestVar('start');
		}
		$products = $products->Limit($limit,$start);
		
		
		if ($this->getRequest()->isAjax())
		{
			$ajaxData = [
				'data' => [],
				'draw' => strtotime('now'),
				'recordsTotal' => $recordsTotal,
				'recordsFiltered' => $finalProductsTotal,
			];
			foreach($products as $product)
			{
				$ajaxData['data'][] = [
					'ID' => $product->ID,
					'BigID' => $product->BigID,
					'Title' => $product->Title,
					'SKU' => $product->sku,
					'Created' => $product->dbObject('Created')->Nice(),
					'Actions' => null,
					'DropdownText' => $product->Title
				];
			}
			header('Content-Type: application/json');
			print json_encode($ajaxData);
			die();
		}
			
		return $this->Customise([
			'Products' => $products
		]);
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
			if ($field instanceof FileAttachmentField) { continue; }
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
				if ($field->hasClass('selectiongroup'))
				{
					$field->addExtraClass('p-0 m-0');
				}
				else
				{
					$field->addExtraClass('border p-3 mb-4');
				}
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
	
	/** Inherited Methods for Managing Data **/
	public function relatedObject()
	{
		if (!$record = $this->currentRecord())
		{
			user_error('Main Record not found');
		}
		if (!$record->Exists())
		{
			user_error('Main Record must be saved first');
		}
		$ComponentName = $this->getRequest()->requestVar('ComponentName') ? $this->getRequest()->requestVar('ComponentName') : $this->getRequest()->param('ComponentName');
		if ( (!$ComponentName) || (!$componentClass = $record->getRelationClass($ComponentName)) )
		{
			return false;
		}
		$components = $record->{$ComponentName}();
		$objectID = $this->getRequest()->requestVar('RelatedID') ? $this->getRequest()->requestVar('RelatedID') : $this->getRequest()->param('RelatedID');
		if ($objectID)
		{
			$object = $components->byID($objectID);
		}
		else
		{
			$object = $components->newObject();
		}
		return $object;
	}
	
	public function relatedObjectForm()
	{
		$relatedObject = $this->relatedObject();
		$record = $this->currentRecord();
		if ( ( (!$record) || (!$record->Exists()) ) && (!$record->CanCreate()) )
		{
			return 'You do not have permission to add this record';
		}
		if ( ($record) && ($record->Exists()) && (!$record->CanEdit()) )
		{
			return 'You do not have permission to edit this record';
		}
		if ( ( (is_object($relatedObject)) && (!$relatedObject->Exists()) ) && (!$relatedObject->CanCreate()) )
		{
			return 'You do not have permission to add this record';
		}
		if ( ($relatedObject) && ($relatedObject->Exists()) && (!$relatedObject->CanEdit()) )
		{
			return 'You do not have permission to edit this record';
		}

		$ComponentName = $this->getRequest()->param('ComponentName') ? $this->getRequest()->param('ComponentName') : $this->getRequest()->requestVar('ComponentName');
		$fields = $relatedObject->getFrontEndFields(['Master' => $record, 'ComponentName' => $ComponentName]);
		
		$fields->push( Forms\HiddenField::create('_ID','')->setValue($record->ID) );
		if ($fields->dataFieldByName('ComponentName'))
		{
			$fields->dataFieldByName('ComponentName')->setValue($ComponentName);
		}
		else
		{
			$fields->push( Forms\HiddenField::create('ComponentName','')->setValue($ComponentName) );
		}
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSaveComponent','Save')
		);
		if ( ($relatedObject->Exists()) && ($relatedObject->CanDelete()) )
		{
			$actions->push(Forms\FormAction::create('doDeleteComponent','Delete')->addExtraClass('btn-danger ml-2'));
		}
		
		$validator = ($relatedObject->hasMethod('getFrontEndRequiredFields')) ? $relatedObject->getFrontEndRequiredFields($fields) : null;
		
		$form = Forms\Form::create(
			$this,
			'relatedObjectForm',
			$fields,
			$actions,
			$validator
		);
		$form->loadDataFrom($relatedObject);
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSaveComponent($data, $form)
	{
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		if (!$component = $this->relatedObject())
		{
			$this->addAlert('Related Component not Found','danger');
			return $this->redirectBack();
		}
		if (!$record = $this->currentRecord())
		{
			$this->addAlert('Record not found','danger');
		}
		$componentName = $data['ComponentName'];
		$form->saveInto($component);
		$component->write();
		$synced = false;
		if ($component->hasMethod('Sync'))
		{
			try {
				$entity = $component->Sync();
				if ($component->hasMethod('loadApiData'))
				{
					$component->loadApiData($entity);
				}
				$synced = true;
			} catch (\Exception $e) {
				$this->addAlert(json_encode($e->getResponseBody()),'warning');
				$this->addAlert($e->getMessage(),'danger');
				return $this->redirectBack();
			}
		}
		
		$record->{$componentName}()->add($component);
		$record->NeedsSync = true;
		$record->write();
		
		$this->addAlert($component->singular_name().' Saved'.($synced ? ' And Synced':''));
		return $this->redirect($this->Link('edit/'.$record->ID));
	}
	
	public function doDeleteComponent($data,$form)
	{
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		if ( (!$component = $this->relatedObject()) || (!$component->Exists()) )
		{
			$this->addAlert('Related Component not Found','danger');
			return $this->redirectBack();
		}
		if (!$record = $this->currentRecord())
		{
			$this->addAlert('Record not found','danger');
		}
		$component->delete();
		$record->NeedsSync = true;
		$record->write();
		$this->addAlert($component->singular_name().' Removed');
		return $this->redirect($this->Link('edit/'.$record->ID));
	}
	
	public function relationremove()
	{
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		if ( (!$component = $this->relatedObject()) || (!$component->Exists()) )
		{
			$this->addAlert('Related Component not Found','danger');
			return $this->redirectBack();
		}
		if (!$record = $this->currentRecord())
		{
			$this->addAlert('Record not found','danger');
		}
		try {
			$component->delete();
			$record->NeedsSync = true;
			$record->write();
		} catch (\Exception $e) {
			throw $e;
		}
		$this->addAlert($component->singular_name().' Removed');
		if ($this->getRequest()->isAjax())
		{
			
		}
		return $this->redirect($this->Link('edit/'.$record->ID));
	}
		
	public function relation()
	{
		return $this;
	}
	
	protected $_currentRecord;
	public function currentRecord()
	{
		if (is_null($this->_currentRecord))
		{
			if (!$className = $this->getRequest()->requestVar('ClassName'))
			{
				$managedClass = $this->Config()->get('managed_class');
			}			
			if ($id = $this->getRequest()->requestVar('_ID'))
			{
				$this->_currentRecord = $managedClass::get()->byID($id);
			}
			elseif ($id = $this->getRequest()->param('ID'))
			{
				$this->_currentRecord = $managedClass::get()->byID($id);
			}
			elseif ($managedClass::singleton()->CanCreate())
			{
				$this->_currentRecord = $managedClass::create();
			}
		}
		return $this->_currentRecord;
	}
	
	public function recordForm()
	{
		$record = $this->currentRecord();
		if ( ( (!$record) || (!$record->Exists()) ) && (!$record->CanCreate()) )
		{
			return 'You do not have permission to add this record';
		}
		if ( ($record) && ($record->Exists()) && (!$record->CanEdit()) )
		{
			return 'You do not have permission to edit this record';
		}

		$fields = $record->getFrontEndFields();
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSave','Save')
		);
		if ( ($record->Exists()) && ($record->CanDelete()) )
		{
			if ($record->BigID)
			{
				$actions->push(Forms\FormAction::create('doUnlink','Unlink')->addExtraClass('btn-danger ml-2'));
			}
			else
			{
				$actions->push(Forms\FormAction::create('doDelete','Delete')->addExtraClass('btn-danger ml-2'));
			}
		}
		
		$validator = $record->getFrontEndRequiredFields($fields);
		
		$form = Forms\Form::create(
			$this,
			'recordForm',
			$fields,
			$actions,
			$validator
		);
		$form->loadDataFrom($record);
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSave($data,$form)
	{
		$record = $this->currentRecord();
		if ( ( (!$record) || (!$record->Exists()) ) && (!$record->CanCreate()) )
		{
			$this->addAlert('You do not have permission to perform this action','danger');
			return $this->redirectBack();
		}
		if ( ($record) && ($record->Exists()) && (!$record->CanEdit()) )
		{
			$this->addAlert('You do not have permission to perform this action','danger');
			return $this->redirectBack();
		}
		$form->saveInto($record);
		try {
			$record->write();
			$message = 'Record Saved';
			if ( ($record->hasMethod('Sync')) && ($entity = $record->Sync()) )
			{
				$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
				$message .= ' & Synced';
			}
			$this->addAlert($message);
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
			throw $e;
		}
		return $this->redirectBack();
	}
	
	public function doDelete()
	{
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		if (!$record = $this->currentRecord())
		{
			$this->addAlert('Record not found','danger');
			return $this->redirectBack();
		}
		if ($record->BigID)
		{
			return $this->doUnlink();
		}
		try {
			$record->delete();
			$this->addAlert('Record Deleted');
			$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, false);
			return $this->redirect($this->Link());
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
			return $this->redirectBack();
		}
	}
	
	public function doUnlink()
	{
		if (!$record = $this->currentRecord())
		{
			$this->addAlert('Record not found','danger');
			return $this->redirectBack();
		}
		if (!$record->BigID)
		{
			return $this->doDelete();
		}
		try {
			$record->Unlink();
			$this->addAlert('Record Removed from BigCommerce');
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
		}
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		return $this->redirectBack();
	}
	
	public function sort_items()
	{
		if ( (!$componentClass = $this->getRequest()->requestVar('component_class')) || (!$itemIDs = $this->getRequest()->requestVar('item_ids')) )
		{
			return $this->httpError(404);
		}
		$componentClass = ClassInfo::class_name($componentClass);
		if ( (!$componentClass) || (!ClassInfo::exists($componentClass)) )
		{
			return 'Cannot find component class';
		}
		$components = $componentClass::get();
		$count = 0;
		$changes = [
			'ids' => $itemIDs,
			'componentClass' => $componentClass,
			'changes' => []
		];
		foreach($itemIDs as $itemID)
		{
			$count++;
			if ($component = $components->byID($itemID))
			{
				$changes['changes'][$component->ID] = $count;
				$component->SortOrder = $count;
				$component->write();
			}
		}
		return json_encode($changes);
	}
}