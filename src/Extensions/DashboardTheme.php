<?php

namespace IQnection\BigCommerceApp\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;
use UncleCheese\Dropzone\FileAttachmentField;
use SilverStripe\Forms;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;


class DashboardTheme extends Extension
{
	private static $theme_name = 'bigcommerceapp';
	private static $hidden = false;
	private static $page_title;
	
	private static $theme_packages = [
		'base',
	];
	
	public $package_includes = [
		'base' => [
			'css' => [
				"assets/vendor/bootstrap/css/bootstrap.min.css",
//				"assets/vendor/fonts/circular-std/style.css",
				"assets/libs/css/style.css",
				"assets/vendor/fonts/fontawesome/css/fontawesome-all.css",
				"assets/vendor/select2/css/select2.min.css",
//				"https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css",
			],
			'js' => [
//				"assets/vendor/jquery/jquery-3.3.1.min.js",
				"assets/vendor/bootstrap/js/bootstrap.bundle.js",
				"assets/vendor/slimscroll/jquery.slimscroll.js",
				"assets/libs/js/main-js.js",
				"assets/vendor/select2/js/select2.full.min.js",
//				"https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js",
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
//		array_unshift($baseThemes, $this->owner->Config()->get('theme_name'));
		$newThemeStack = [
			$this->owner->Config()->get('theme_name'),
			'$public',
			'$default'
		];
//		$additionalThemes = array_diff($baseThemes, $newThemeStack);
//		$newThemeStack = array_merge($newThemeStack,$additionalThemes);
//		array_pop($baseThemes);
		// Put the theme at the top of the list
//		$baseThemes[] = $this->owner->Config()->get('theme_name');
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