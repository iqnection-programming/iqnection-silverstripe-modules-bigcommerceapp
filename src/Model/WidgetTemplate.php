<?php

namespace IQnection\BigCommerceApp\Model;

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
use IQnection\BigCommerceApp\Model\ApiObjectInterface;

class WidgetTemplate extends DataObject implements ApiObjectInterface
{	
	private static $extensions = [
		ApiObject::class
    ];
	
	private static $entity_class = \IQnection\BigCommerceApp\Entities\WidgetTemplateEntity::class;
	
	private static $table_name = 'BCWidgetTemplate';
	
	private static $template_title = 'Base';
	private static $template_path = null;
	
	public function getTitle()
	{
		return $this->Config()->get('template_title');
	}
	
	public function loadApiData($data)
	{
		if ($data)
		{
			$this->BigID = $data->uuid;
			$this->Title = $data->name;
		}
		else
		{
			$this->BigID = null;
		}
		$this->RawData = json_encode($data);
		return $this;
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
		
	public function ApiData()
	{
		$html = (string) $this->forTemplate();
		$html = preg_replace('/\n|\t|\r/','',$html);
		$data = [
			'name' => $this->getTitle(),
			'template' => $html,
		];
		if ($this->BigID)
		{
			$data['uuid'] = $this->BigID;
			$data['BigID'] = $this->BigID;
		}
		$this->extend('updateApiData',$data);
		return $data;
	}
	
	public function Unlink()
	{ }
		
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
	
	public function validateHandlebars()
	{
		$html = (string) $this->getTemplateHtml();
		$html = $this->cleanHTML($html);
		$errors = [];
		if (preg_match('/{{[^#][^}]+?[^a-zA-Z0-9_|.}\s]+}}/',$html,$varMatches))
		{
			$errors[] = [
				'error' => 'Invalid variable call',
				'found' => $varMatches
			];
		}
		$noHtml = preg_replace('/[^{}]*?({+[^}]+}+)[^{}]*/','',$html);
		$noVariables = preg_replace('/{{2,3}[^#}\/]+}{2,3}/','',$noHtml);
		if ( (preg_match('/{{#/',$noVariables)) && (preg_match('/{{#(\w+).*?{{\/\1}}/',$noVariables,$blockMatches)) )
		{
			$errors[] = [
				'error' => 'Possible unclosed blocks',
				'found' => $blockMatches
			];
		}
		return $errors;
	}
	
	protected function cleanHTML($html)
	{
		// remove beginng hidden characters
		$html = trim($html);
		// remove multiple tabs
		$html = preg_replace('/\t/',"",$html);
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