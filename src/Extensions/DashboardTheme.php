<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use UncleCheese\Dropzone\FileAttachmentField;
use SilverStripe\Forms;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use IQnection\BigCommerceApp\App\Main;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;


class DashboardTheme extends Extension
{
	private static $theme_name = 'bigcommerceapp';
	private static $hidden = false;
	private static $page_title;
	
	protected $package_name = 'iqnection-modules/silverstripe-bigcommerceapp';
	
	private static $theme_packages = [
		'base',
	];
	
	public $package_includes = [
		'base' => [
			'css' => [
				"assets/vendor/bootstrap/css/bootstrap.min.css",
				"assets/libs/css/style.css",
				"assets/vendor/fonts/fontawesome/css/fontawesome-all.css",
				"assets/vendor/select2/css/select2.min.css",
			],
			'js' => [
				"assets/vendor/bootstrap/js/bootstrap.bundle.js",
				"assets/vendor/slimscroll/jquery.slimscroll.js",
				"assets/libs/js/main-js.js",
				"assets/vendor/select2/js/select2.full.min.js",
				"assets/vendor/shortable-nestable/Sortable.min.js",
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
				"assets/vendor/datatables/js/data-table.js",
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
				"assets/vendor/bootstrap/js/bootstrap.bundle.js",
				"assets/vendor/slimscroll/jquery.slimscroll.js",
				"assets/vendor/parsley/parsley.js",
				"assets/libs/js/main-js.js",
			]
		],
		'custom' => [
			'css' => [
				"css/app.scss"
			],
			'js' => [
				"javascript/app.js"
			]
		]
	];
	
	public function ModuleName()
	{
		if (!$Title = $this->owner->Config()->get('page_title', Config::UNINHERITED))
		{
			$nav = $this->owner->Config()->get('nav_links', Config::UNINHERITED);
			$Title = key($nav);
		}
		return $Title;
	}
	
	public function Dashboard($app = null)
	{
    	$appClass = Main::class;
    	$apps = Main::Config()->get('apps');
		if (isset($apps[$app]))
		{
		  $appClass = $apps[$app];
		}
		return Injector::inst()->get($appClass);
	}
	
	public function Link($action = null)
	{
		return \SilverStripe\Control\Controller::join_links('/',$this->owner->Config()->get('url_segment'),$action);
	}
	
	public function AbsoluteLink($action = null)
	{
		return preg_replace('/^http\:/','https:',\SilverStripe\Control\Director::absoluteURL($this->owner->Link($action)));
	}
	
	public function onAfterInit()
	{
		$this->setDashboardTheme();
		$this->loadRequirements();
		$this->owner->getRequest()->addHeader('X-Frame-Options','*');
	}
	
	public static $_includedCss = [];
	public function combineCssFiles($css)
	{
		$themeName = $this->owner->Config()->get('theme_name');
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
				// see if our site theme has an override stylesheet
				$packageResource = ModuleResourceLoader::resolveResource($this->package_name.':'.$cssFile.$ext);
				if ($themeResource = ThemeResourceLoader::inst()->findThemedResource($cssFile.$ext))
				{
					$CssFiles[$this->package_name.':'.$cssFile] = $themeResource;
				}
				// no override, find teh package file path and include it
				elseif ($packageResource->exists())
				{
					$CssFiles[$this->package_name.':'.$cssFile] = $packageResource->getRelativePath();
				}
				// see if our theme has a stylesheet extension to include along with the package stylesheet
				if ($themeResourceExtension = ThemeResourceLoader::inst()->findThemedResource($cssFile.'_extension'.$ext))
				{
					$CssFiles[$cssFile.'_extension'] = $themeResourceExtension;
				}
			}
		}
		if (count($CssFiles))
		{
			Requirements::combine_files('dashboard-'.md5(json_encode($CssFiles)).'.css', $CssFiles);
		}
	}
	
	public static $_includedJs = [];
	public function combineJsFiles($js)
	{
		$themeName = $this->owner->Config()->get('theme_name');
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
			if ($JsFilePath = ThemeResourceLoader::inst()->findThemedResource($jsFile.$ext,array($themeName)))
			{
				$JsFiles[$this->package_name.':'.$jsFile] = $JsFilePath;
			}
			elseif ($JsFilePath = ThemeResourceLoader::inst()->findThemedResource('javascript/'.$jsFile.$ext,array($themeName)))
			{
				$JsFiles[$this->package_name.':'.$jsFile] = $JsFilePath;
			}
			// no override, find teh package file path and include it
			elseif ($JsFilePath = ModuleResourceLoader::resourcePath($this->package_name.':'.$jsFile.$ext,array($themeName)))
			{
				$JsFiles[$this->package_name.':'.$jsFile] = $this->package_name.':'.$jsFile.$ext;
			}
			// see if our theme has a stylesheet extension to include along with the package stylesheet
			if ($JsFilePath = ThemeResourceLoader::inst()->findThemedResource($jsFile.'_extension'.$ext,array($themeName)))
			{
				$JsFiles[jsFile.'_extension'] = $JsFilePath;
			}
			elseif ($JsFilePath = ThemeResourceLoader::inst()->findThemedResource('javascript/'.$jsFile.'_extension'.$ext,array($themeName)))
			{
				$JsFiles[jsFile.'_extension'] = $JsFilePath;
			}
		}

		if (count($JsFiles))
		{
			Requirements::combine_files('dashboard-'.md5(json_encode($JsFiles)).'.js', $JsFiles);	
		}
	}
	
	public function loadThemePackage($packageName)
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

	public function loadThemePackages()
	{
		foreach($this->owner->Config()->get('theme_packages') as $packageName)
		{
			$this->loadThemePackage($packageName);
		}
		$this->loadThemePackage('custom');
	}
	
	public function loadRequirements()
	{
		$themeName = $this->owner->Config()->get('theme_name');
		Requirements::set_combined_files_folder('combined/'.$themeName);
		$this->loadThemePackages();
		$this->owner->invokeWithExtensions('updateRequirements');
	}
	
	public function setDashboardTheme()
	{
		$baseThemes = SSViewer::get_themes();
		$newThemeStack = [
//			$this->owner->Config()->get('theme_name'),
			'$public',
			'$default'
		];
		SSViewer::set_themes(array_unique($newThemeStack));
		$this->owner->invokeWithExtensions('updateDashboardTheme');
	}
	
	protected $_Alerts = [];
	public function setAlerts($alerts)
	{
		$this->owner->getRequest()->getSession()->set('alerts',$alerts);
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
		$this->owner->setAlerts($this->_Alerts);
		return $this;
	}
	
	protected $_AlertsOut;
	public function Alerts()
	{
		if ( (is_null($this->_AlertsOut)) && (is_array($this->owner->getRequest()->getSession()->get('alerts'))) )
		{
			$this->_AlertsOut = ArrayList::create();
			foreach($this->owner->getRequest()->getSession()->get('alerts') as $alert)
			{
				$this->_AlertsOut->push(ArrayData::create($alert));
			}
			$this->owner->setAlerts(false);
		}
		return $this->_AlertsOut;
	}
}