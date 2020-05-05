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
use SilverStripe\Control\Director;
use SilverStripe\Control\Cookie;

class Main extends Controller
{
	const SKIP_SYNC_SESSION_VAR = 'skip-next-sync';
	
	private static $url_segment = '_bc';
	private static $managed_class;
	
	private static $extensions = [
		\IQnection\BigCommerceApp\Extensions\DashboardTheme::class
	];
	
	private static $allowed_actions = [
		'ping',
		'index',
		'search_api',
		'recordForm',
		'relation',
		'relatedObjectForm',
		'relationremove',
		'edit',
		'DashboardLoginForm',
		'sort_items',
		'doDelete',
		'doUnlink',
		'pull',
		'dismissnotifications',
		'apidata' => 'ADMIN'
	];
	
	private static $public_actions = [
		'load',
		'uninstall',
		'installerror',
		'ping'
	];
	
	private static $url_handlers = [
		'notification//$subAction!/$ID!' => 'updateNotification',
		'edit/$ID/relationremove/$ComponentName!/$RelatedID' => 'relationremove',
		'edit/$ID/relation/$ComponentName!/$RelatedID' => 'relation',
		'edit/$ID/pull' => 'pull'
	];

	private static $apps = [
		'Main' => Main::class,
		'Products' => Products::class,
		'Categories' => Categories::class,
		'File Manager' => FileManager::class,
		'Widgets' => Widgets::class,
		'SilverStripe' => SSAdminRedirect::class,
		'Logs' => AppLogs::class,
		'Webhooks' => Webhooks::class
	];
	
	private static $nav_links = [
		'Home' => [
			'path' => '',
			'icon' => 'home'
		]
	];
	
	public function pull()
	{
		$record = $this->currentRecord();
		try {
			$record->Pull();
			$this->addAlert('Data Synced');
		} catch (\Exception $e) {
			$this->addAlert('There was an error syncing the data','danger');
			$this->addAlert(print_r($e->getMessage(),1),'danger');
			if (method_exists($e, 'getResponseBody'))
			{
				$this->addAlert(print_r($e->getResponseBody(),1),'danger');
			}
		}
		return $this->redirect($this->Link('edit/'.$record->ID));
	}
	
	public function apidata()
	{
		print "<pre><xmp>";
		if ($relation = $this->relatedObject())
		{
			print "-----API Data\n";
			print_r($relation->ApiData()); 
		}
		elseif ($record = $this->currentRecord())
		{
			print "-----Object API Data\n";
			print_r($record->ApiData());
			print "-----Entity API Data\n";
			print_r($record->Entity()->ApiData());
			print "\n\n-----Raw API Data\n";
			print_r($record->RawApiData()->toMap());
			print "\n\n-----Import Data\n";
			print_r(json_decode($record->ImportData));
			
		}
		print '</xmp></pre>';
		die();
	}
		
	public function ping()
	{
		return (bool) (Security::getCurrentUser());
	}
	
	public function init()
	{
		parent::init();
		if (!Security::getCurrentUser())
		{
			$publicActions = $this->Config()->get('public_actions');
			$currentAction = $this->getRequest()->param('Action');
			if ( (!in_array($currentAction, $publicActions)) && (!array_key_exists($currentAction, $publicActions)) )
			{
				return Auth::permissionFailure($this);
			}
		}
		if (array_key_exists('bc_show_all_apps',$_GET))
		{
			Cookie::set('bc_show_all_apps',$_GET['bc_show_all_apps']);
		}
		if (!$this->Config()->get('url_segment',Config::UNINHERITED))
		{
			user_error(get_class($this)." doesn't have a URL segment declared");
		}
		Requirements::customScript('window._search_url = "'.$this->AbsoluteLink('search_api').'";
window._sort_url = "'.$this->AbsoluteLink('sort_items').'";
window._ping_url = "'.$this->AbsoluteLink('ping').'";');
		Requirements::javascript("https://code.jquery.com/jquery-3.4.1.min.js");
		Requirements::javascript('silverstripe/admin:thirdparty/tinymce/tinymce.min.js');
		Requirements::css('silverstripe/admin:client/dist/styles/editor.css');
		Requirements::customScript(
<<<JS
$('[data-editor="tinyMCE"]').each(function(){
	var config = $(this).data('config');
	config.skin = "silverstripe";
	config.selector = '#'+$(this).attr('id');
	tinymce.init(config);
});
JS
		);		
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
		$searchTerm = trim($search['value']);
		if ($searchTerm)
		{
			$records = $records->FilterAny([
				'BigID:ExactMatch' => $searchTerm,
				'Title:PartialMatch' => $searchTerm,
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
		
		
		if (Director::is_ajax())
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
		$searchTerm = trim($search['value']);
		if ($searchTerm)
		{
			$products = $products->FilterAny([
				'BigID:ExactMatch' => $searchTerm,
				'sku:PartialMatch' => $searchTerm,
				'Title:PartialMatch' => $searchTerm,
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
		
		
		if (Director::is_ajax())
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
		if (!$Title = $this->Config()->get('page_title'))
		{
			$nav = $this->Config()->get('nav_links', Config::UNINHERITED);
			$Title = key($nav);
		}
//		if ( ($action = $this->getAction()) && ($action != 'index') )
//		{
//			$Title = ucwords($action);
//		}

		if ( ($currentRecord = $this->currentRecord()) && ($currentRecord->Exists()) )
		{
			$Title .= ' | '.$currentRecord->getTitle();
		}
		return $Title;
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
			$notifications = $member->Notifications()->Sort('ID','DESC');
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
			if ( (array_key_exists('dev', $details)) && ($details['dev']) && (!Director::isDev()) )
			{
				continue;
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
		$showAll = Cookie::get('bc_show_all_apps');
		foreach($this->Config()->get('apps') as $app)
		{
			$singleton = Injector::inst()->get($app);
			if ( (!$singleton->Config()->get('hidden')) || ($showAll) )
			{
				foreach($singleton->NavLinks() as $title => $details)
				{
					$details = (!is_array($details)) ? [$details] : $details;
					if ( (array_key_exists('dev', $details)) && ($details['dev']) && (!Director::isDev()) )
					{
						continue;
					}
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
		}
		$this->extend('updateMenu',$links);
		return $links;
	}
	
	public function BootstrapForm(&$form)
	{
		\SilverStripe\Forms\HTMLEditor\HTMLEditorConfig::set_active_identifier('bigcommerce');
		$bc_config = \SilverStripe\Forms\HTMLEditor\HTMLEditorConfig::get('bigcommerce');
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
			
			if ($field instanceof Forms\HTMLEditor\HTMLEditorField)
			{
				$field->setEditorConfig($bc_config);
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
			$action->addExtraClass('btn mt-2 mr-2 btn-primary');
		}
	}
	
	public function dismissnotifications()
	{
		foreach(Security::getCurrentUser()->Notifications()->Exclude('Status',Notification::STATUS_DISMISSED) as $notification)
		{
			$notification->Status = Notification::STATUS_DISMISSED;
			$notification->write();
		}
		$this->addAlert('All Notifications Dismissed');
		return $this->redirectBack();
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
		if ( (is_object($message)) || (is_array($message)) )
		{
			$message = print_r($message,1);
		}
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
		return;
//		BCLog::info('Initial Install', $this->getRequest()->requestVars());
//		if ($installStatus = $this->getRequest()->getVar('external_install'))
//		{
//			return $this->confirmInstall($installStatus);
//		}
//		if (!$member = Security::getCurrentUser())
//		{
//			$message = 'Before you can install this app, you must open the SilverStripe admin in another tab and have an active login session. 
//			Once this is ready, come back and initiate the install process again.';
//			return Security::permissionFailure($this, $message);
//			return $this->Customise(['Content' => $message])->renderWith(['IQnection/BigCommerceApp/App/NoAuth']);
//		}
//		$siteconfig = SiteConfig::current_site_config();
//		$code = $this->getRequest()->getVar('code');
//		$scope = $this->getRequest()->getVar('scope');
//		$context = $this->getRequest()->getVar('context');
//		
//		$client = new \GuzzleHttp\Client();
//		$postBack = [
//			'client_id' => Client::Config()->get('client_id'),
//			'client_secret' => Client::Config()->get('client_secret'),
//			'code' => $code,
//			'scope' => $scope,
//			'grant_type' => 'authorization_code',
//			'redirect_uri' => $siteconfig->getBigCommerceAuthCallbackUrl(),
//			'context' => $context
//		];
//		BCLog::info('Installing Postback', $postBack);
//		$response = $client->request('POST', $this->Config()->get('install_post_back_url'), [
//			'headers' => [
//				'Content-Type' => 'application/json'
//			],
//			'json' => $postBack
//		]);
//		$responseData = json_decode((string) $response->getBody());
//		BCLog::info('Install Postback Response', $responseData);
//		if ($access_token = $responseData->access_token)
//		{
//			$member = Security::getCurrentUser();
//			$member->BigCommerceID = $responseData->user->id;
//			$member->write();
//
//			$siteconfig->BigCommerceStoreHash = preg_replace('/.*?\/([a-zA-Z0-9_-]+)/','$1',$responseData->context);
//			$siteconfig->BigCommerceApiAccessToken = $access_token;
//			$siteconfig->BigCommerceApiScope = $responseData->scope;
//			$siteconfig->write();
//			return $this;
//		}
//		return $this->redirect($this->Link('installerror'));
	}
	
	public function installerror()
	{
//		return $this->Customise(['HideNav' => true]);
	}
	
	/**
	 * Sends a callback to BigCommerce to let their server know if the install was successfull
	 */
	private function confirmInstall($installStatus)
	{
//		$successUrl = sprintf('https://login.bigcommerce.com/app/%s/install/succeeded', Client::Config()->get('client_id'));
//		$failUrl = sprintf('https://login.bigcommerce.com/app/%s/install/failed', Client::Config()->get('client_id'));
//		$client = new \GuzzleHttp\Client();
//		$callUrl = (empty($installStatus)) ? $failUrl : $successUrl;
//		$response = $client->request($callUrl);
//		if (empty($installStatus))
//		{
//			return $this->renderWith(['BigCommerceInstallError']);
//		}
//		return $this->renderWith(['BigCommerceInstallComplete']);
	}
	
	/**
	 * callback used when uninstalling the app from a BigCommerce store
	 */	
	public function uninstall()
	{
//		$siteconfig = SiteConfig::current_site_congfig();
//		$siteconfig->BigCommerceApiAccessToken = $access_token;
//		$siteconfig->write();
//		return $this->redirect($this->Link());
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
			Forms\FormAction::create('doSaveComponent','Save')->addExtraClass('btn-success')
		);
		if ( ($relatedObject->Exists()) && ($relatedObject->CanDelete()) )
		{
			$actions->push(Forms\FormAction::create('doDeleteComponent','Delete')->addExtraClass('btn-outline-danger ml-2'));
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
		$record->{$componentName}()->add($component);
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
				$this->addAlert($e->getMessage(),'danger');
				if (method_exists($e, 'getResponseBody'))
				{
					$this->addAlert(json_encode($e->getResponseBody()),'warning');
				}
				return $this->redirectBack();
			}
		}
		
		
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
			if ($managedClass)
			{
				if ($id = $this->getRequest()->requestVar('_ID'))
				{
					$this->_currentRecord = $managedClass::get()->byID($id);
				}
				elseif ($id = $this->getRequest()->param('ID'))
				{
					$this->_currentRecord = $managedClass::get()->byID($id);
				}
				elseif ( ($managedClass::singleton()->hasMethod('CanCreate')) && ($managedClass::singleton()->CanCreate()) )
				{
					$this->_currentRecord = $managedClass::create();
				}
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
//		if ( ($record->Exists()) && ($record->CanDelete()) )
//		{
//			if ($record->BigID)
//			{
//				$actions->push(Forms\FormAction::create('doUnlink','Unlink')->addExtraClass('btn-danger ml-2'));
//			}
//			else
//			{
//				$actions->push(Forms\FormAction::create('doDelete','Delete')->addExtraClass('btn-danger ml-2'));
//			}
//		}
		
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
			if (method_exists($e, 'getResponseBody'))
			{
				$this->addAlert($e->getResponseBody(),'danger');
			}
//			throw $e;
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