<?php

namespace IQnection\BigCommerceApp\Widgets;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms;
use BigCommerce\Api\v3\Model\PlacementRequest;
use BigCommerce\Api\v3\Model\WidgetRequest;
use BigCommerce\Api\v3\Model\WidgetTemplateRequest;
use IQnection\BigCommerceApp\Client;
use IQnection\BigCommerceApp\App\Main;
use SilverStripe\View\ThemeResourceLoader;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\SSViewer;
use IQnection\BigCommerceApp\Model\ApiObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Security\Security;
use SilverStripe\Control\Director;

class WidgetTemplate extends ApiObject
{	
	use \IQnection\BigCommerceApp\Traits\ApiModel;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	
	private static $table_name = 'BCWidgetTemplate';
	
	private static $template_title = 'Base';
	private static $template_path = null;
	
	private static $summary_fields = [
		'Active.Nice' => 'Active',
		'Title' => 'Title',
	];
	
	public function CanCreate($member = null, $context = [])
	{
		return (($member = Security::getCurrentUser()) && ($member->ID == 1));
	}
	
	public function CanEdit($member = null, $context = [])
	{
		return (($member = Security::getCurrentUser()) && ($member->ID == 1));
	}
	
	public function CanDelete($member = null, $context = [])
	{
		return (($member = Security::getCurrentUser()) && ($member->ID == 1));
	}
	
	public function getTitle()
	{
		return $this->Config()->get('template_title');
	}
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
//		$fields->addFieldToTab('Root.Main', Forms\DropdownField::create('ListItemClassName','List Item Type')
//			->setSource($this->getListItemClassNames())
//			->setEmptyString('-- Select --') );
		return $fields;
	}
	
	public function onBeforeWrite()
	{
		parent::onBeforeWrite();
		$this->Title = $this->Config()->get('template_title');
	}
	
	public function validate()
	{
		$result = parent::validate();
		if (WidgetTemplate::get()->Exclude('ID',$this->ID)->Find('Title',$this->Title))
		{
			$result->addError('This title is already used for another record');
		}
		if ($templatePath = $this->Config()->get('template_path'))
		{
			$baseThemes = SSViewer::get_themes();
			$appTheme = Main::Config()->get('theme_name');
			$baseThemes[] = $appTheme;
			SSViewer::set_themes(array_unique($baseThemes));
			$resourceLoader = Injector::inst()->get(ThemeResourceLoader::class);
			if (!$resourceLoader->findTemplate($templatePath, [$appTheme]))
			{
				$result->addError('I cannot find a template with the name '.$templatePath);
			}
		}
		return $result;
	}
	
	public function loadFromApi($data)
	{
		$this->Title = $data->getName();
		return parent::loadFromApi($data);
	}
	
	public function ApiData()
	{
		return [
			'name' => $this->getTitle(),
			'template' => (string) $this->forTemplate(),
		];
	}
	
	protected static $_is_pushing = false;
	public function sync($data)
	{
		$Client = $this->ApiClient();
		if ($this->BigID)
		{
			$template = $Client->updateWidgetTemplate($this->BigID, new WidgetTemplateRequest( $data ) )->getData();
			BcLog::info('Updated Widget Template', $this->BigID);
		}
		else
		{
			$template = $Client->createWidgetTemplate( new WidgetTemplateRequest( $data ) )->getData();	
			$this->BigID = $template->getUuid();			
			BcLog::info('Created Widget Template ',$template);
		}
		return $template;
	}
	
	public function onBeforeDelete()
	{
		parent::onBeforeDelete();
		if ($this->BigID)
		{
			try {
				$Client = $this->ApiClient();
				$Client->deleteWidgetTemplate($this->BigID);
				BcLog::info('Deleted Widget Template', $this->BigID);
			} catch (\Exception $e) {
				BcLog::error('Delete Error', $e->getMessage());
				throw new \SilverStripe\ORM\ValidationException('Error saving widget template: '.$e->getMessage());
			}
		}
	}
	
	public function getTemplateHtml()
	{
		if ($template_path = $this->Config()->get('template_path'))
		{
			return $this->Customise(['Handlebars' => true])->renderWith($template_path);
		}
		return null;
	}
	
	public function forTemplate()
	{
		$html = (string) $this->getTemplateHtml();
		return $html;
	}
	
	protected function cleanHTML($html)
	{
		// remove beginng hidden characters
		$html = preg_replace('/^(\t|\n|\r|\s)+/','',$html);
		// remove trailing hidden characters
		$html = preg_replace('/(\t|\n|\r|\s)+$/','',$html);
		// remove multiple tabs
		$html = preg_replace('/\t{2,}/',"\t",$html);
		// remove multiple line breaks
		$html = preg_replace('/(\n|\r){2,}/',"\n",$html);
		// set all paths to absolute
		$html = preg_replace_callback('/((?:src|href|action)=[\'\"])([^\'\"]*)([\'\"])/',function($matches){
			if ( (preg_match('/^\/[^\/]/',$matches[2])) && (!preg_match('/^https?/',$matches[2])) )
			{
				$matches[2] = Director::absoluteURL($matches[2]);
			}
			return $matches[1].$matches[2].$matches[3];
		},$html);
		return $html;
	}
}