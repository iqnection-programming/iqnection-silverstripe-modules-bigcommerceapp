<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use IQnection\BigCommerceApp\Model\Widget;
use IQnection\BigCommerceApp\Entities\WidgetPlacementEntity;
use IQnection\BigCommerceApp\Entities\WidgetTemplateEntity;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Model\WidgetListItem;
use IQnection\BigCommerceApp\Model\WidgetTemplate;
use IQnection\BigCommerceApp\Model\WidgetPlacement;
use IQnection\BigCommerceApp\Client;
use BigCommerce\Api\v3\Api\WidgetApi;
use SilverStripe\Core\ClassInfo;

class Widgets extends Main
{
	const SKIP_SYNC_SESSION_VAR = 'skip-widget-sync';
	private static $managed_class = Widget::class;
	private static $url_segment = '_bc/widgets';
	private static $allowed_actions = [
		'_test' => 'ADMIN',
		'widgetconfig',
		'items',
		'item',
		'add',
		'widgetForm',
		'edit',
		'edititem',
		'sync',
		'listItemForm',
		'delete',
		'deleteitem',
		'syncHomePage',
		'placement',
		'deleteplacement',
		'PlacementForm',
		'unlinktemplate'
	];
	
	private static $url_handlers = [
//		'edit/$ID/config' => 'widgetconfig',
//		'edit/$ID/config/$ComponentName!/$ComponentID' => 'edititem',
//		'edititem/$WidgetID/$ComponentName/$ComponentID' => 'edititem',
		'deleteitem/$WidgetID/$ComponentName/$ComponentID' => 'deleteitem',
		'edit/$ID/place/$ComponentName/$RelatedID' => 'placement'
	];
	
	private static $nav_links = [
		'Widgets' => [
			'path' => '',
			'icon' => 'th-large'
		]
	];
	
	private static $theme_packages = [
		'forms',
		'datatables'
	];
	
	public function syncHomePage()
	{
		$data = [];
		switch($this->getRequest()->param('ID'))
		{
			default:
				break;
			case 'placements':
				$data = $this->getPlacements();
				break;
			case 'template':
				if ($widgetTemplate = WidgetTemplate::get()->Find('Title','Home Page'))
				{
					$data['BigCommerce ID'] = $widgetTemplate->BigID;
					$data['html'] = (string) $widgetTemplate->forTemplate();
				}
				break;
			case 'widget':
//				if ($widget = Widget::get()->
				break;
		}
		$actions = ArrayList::create([
			ArrayData::create([
				'Link' => $this->Link('syncHomePage/placements'),
				'Title' => 'Get Placements'
			]),
			ArrayData::create([
				'Link' => $this->Link('syncHomePage/template'),
				'Title' => 'Generate Template'
			])
		]);
		return $this->Customise(['Actions' => $actions, 'Data' => print_r($data,1)]);
	}
	
	public function index()
	{
		return $this->Customise([
			'Widgets' => Widget::get(),
			'BCWidgets' => $this->getBCWidgets(),
			'BCWidgetTemplates' => $this->getBCWidgetTemplates()
		]);
	}
	
	public function delete()
	{
		try {
			if ($widget = Widget::get()->byID($this->getRequest()->param('ID')))
			{
				$widget->Unlink();
				$widget->delete();
				$this->addAlert('Widget Deleted');
				return $this->redirectBack();
			}
			$bcWidgets = $this->getBCWidgets();
			if ($bcWidget = $bcWidgets->Find('uuid',$this->getRequest()->param('ID')))
			{
				$widget = Widget::create();
				$widget->BigID = $this->getRequest()->param('ID');
				$widget->Unlink();
				return $this->redirectBack();
			}
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
		}
	}
	
	public function getBCWidgets()
	{
		$widget = \singleton(Widget::class);
		$client = $widget->ApiClient();
		$bcWidgets = $client->getWidgets();
		$widgetData = ArrayList::create();
		foreach($bcWidgets->getData() as $bcWidget)
		{
			$widgetData->push(ArrayData::create($bcWidget->get()));
		}
		return $widgetData;
	}
	
	public function getBCWidgetTemplates()
	{
		$widgetTemplate = \singleton(WidgetTemplate::class);
		$client = $widgetTemplate->ApiClient();
		$bcWidgetTemplates = $client->getWidgetTemplates();
		$widgetTemplateData = ArrayList::create();
		foreach($bcWidgetTemplates->getData() as $bcWidgetTemplate)
		{
			$widgetTemplateData->push(ArrayData::create($bcWidgetTemplate->get()));
		}
		return $widgetTemplateData;
	}
	
	protected $_placements;
	protected function getPlacements()
	{
		if (is_null($this->_placements))
		{
			$this->_placements = [];
			$clientClass = WidgetApi::class;
			$Client = Client::inst()->Api(md5($clientClass), $clientClass);
			$this->_placements = $Client->getContentRegions( [ 'templateFile' => 'pages/home' ] )->getData();
		}
		return $this->_placements;
	}
	
	public function edit()
	{
		if ( (!$this->getRequest()->isPost()) && (!$this->getRequest()->getSession()->get(static::SKIP_SYNC_SESSION_VAR)) )
		{
			if ($widget = $this->currentRecord())
			{
				try {
					$widget->SyncPlacements();
				} catch (\Exception $e) {
					$this->addAlert('There was an error syncing the widget','danger');
					$this->addAlert($e->getMessage(),'danger');
					if (method_exists($e, 'getResponseBody'))
					{
						$this->addAlert(json_encode($e->getResponseBody()),'danger');
					}
				}
			}
		}
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, false);
		return $this;
	}
	
	public function sync()
	{
		if ($widget = $this->currentRecord())
		{
			try {
				$widget->Sync();
				$this->addAlert('Widget Configuration Synced');
			} catch (\Exception $e) {
				$this->addAlert('There was an error syncing the widget','danger');
				$this->addAlert($e->getMessage(),'danger');
				if (method_exists($e, 'getResponseBody'))
				{
					$this->addAlert(json_encode($e->getResponseBody()),'danger');
				}
			}
		}
		return $this->redirectBack();
	}
		
	public function PlacementForm()
	{
		$widget = $this->currentRecord();
		$currentPlacement = $this->relatedObject();
		$fields = Forms\FieldList::create();
		$fields->push( Forms\HeaderField::create('pages-header','Page',3)->addExtraClass('mb-0') );
		$fields->push( $selectionGroup = Forms\SelectionGroup::create('template_file', [])->removeExtraClass('mt-0') );
		$postInst = WidgetPlacementEntity::create([]);
		if ($this->getRequest()->isPost())
		{
			$postVars = $this->getRequest()->postVars();
			$postInst->setTemplateConfig($postVars['template_file']);
			$postInst->entity_id = $postVars['entity_id'][$postInst->template_file];
			$postInst->region = $postVars['region'][$postInst->template_file];
		}
		foreach(WidgetPlacementEntity::getTemplateFiles()->Filter('Enabled',true) as $templateConfig)
		{
			$regions = [];
			foreach(WidgetPlacementEntity::getContentRegions($templateConfig->Name) as $regionRecord)
			{
				$regions[$regionRecord->name] = ucwords(preg_replace('/_/',' ',$regionRecord->name));
			}
			if (count($regions))
			{
				$selectionGroup->push( $selectionGroup_item = Forms\SelectionGroup_Item::create($templateConfig->Name, [], $templateConfig->Title) );
				if ( ($EntityClass = $templateConfig->EntityClass) && (class_exists($EntityClass)) )
				{
					$source = [];
					try {
						// to trick the validator, we have to set the selected value as an available value
						// see if a value has been selected
						if ($postInst->template_file == $templateConfig->template_file)
						{
							if ($postInstPlacementResource = $postInst->getPlacementResource())
							{
								$source[$postInstPlacementResource->id] = $postInstPlacementResource->dropdownTitle();
							}
						}
					} catch (\Exception $e) {
						$this->addAlert($e->getResponseBody(),'danger');
					}
					$selectionGroup_item->push($entity_id = Forms\DropdownField::create('entity_id['.$templateConfig->Name.']', $templateConfig->Title)
						->setAttribute('data-ajax-call','1')
						->setAttribute('data-resource-type',$templateConfig->Title)
						->setAttribute('data-id-field','BigID')
						->setSource($source)
						->setEmptyString('-- Select --') );
				}
				// get regions on the category template
				$selectionGroup_item->push($region = Forms\DropdownField::create('region['.$templateConfig->Name.']', 'Region')
					->setSource($regions)
					->setEmptyString('-- Select --') );

			}
		}
		
		$fields->push( Forms\HiddenField::create('_ID','',$widget->ID));
		$fields->push( Forms\HiddenField::create('ComponentName','','Placements'));
		$fields->push( Forms\HiddenField::create('RelatedID','',$currentPlacement->ID));
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSavePlacement','Save')
		);
		
		$validator = Forms\RequiredFields::create();
		
		$form = Forms\Form::create(
			$this,
			'PlacementForm',
			$fields,
			$actions,
			$validator
		);
		// set defaults
		if ($currentPlacement->Exists())
		{
			if ( ($entity_id_field = $fields->dataFieldByName('entity_id['.$currentPlacement->template_file.']')) && ($resource = $currentPlacement->getPlacementResource()) )
			{
				$entityIDs = $entity_id_field->getSource();
				$entityIDs[$resource->BigID] = $resource->dropdownTitle();
				$entity_id_field->setSource($entityIDs);
			}
			$form->loadDataFrom([
				'entity_id' => [$currentPlacement->template_file => $currentPlacement->entity_id],
				'region' => [$currentPlacement->template_file => $currentPlacement->region],
				'template_file' => $currentPlacement->template_file
			]);
		}
		if ($this->getRequest()->isPost())
		{
			$form->loadDataFrom($postInst);
		}
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSavePlacement($data,$form)
	{
		$widget = $this->currentRecord();
		$currentPlacement = $this->relatedObject();
		$currentPlacement->WidgetID = $widget->ID;
		$currentPlacement->template_file = $data['template_file'];
		$currentPlacement->region = $data['region'][$currentPlacement->template_file];
		$currentPlacement->entity_id = (isset($data['entity_id'][$currentPlacement->template_file])) ? $data['entity_id'][$currentPlacement->template_file] : null;
		
		try {
			$currentPlacement->Sync();
			$currentPlacement->write();
			$this->addAlert('Widget Placed');
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(), 'danger');
		}
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		return $this->redirect($this->Link('edit/'.$widget->ID));
	}
	
	public function deleteplacement()
	{
		$BigID = $this->getRequest()->param('ID');
		$inst = WidgetPlacementEntity::singleton();
		$inst->BigID = $BigID;
		$inst->delete();
		$this->getRequest()->getSession()->set(static::SKIP_SYNC_SESSION_VAR, true);
		return $this->redirectBack();
	}
	
	public function unlinktemplate()
	{
		$entity = WidgetTemplateEntity::create([
			'BigID' => $this->getRequest()->param('ID')
		]);
		try {
			$entity->Unlink();
			$this->addAlert('Widget Template Removed');
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
		}
		return $this->redirectBack();
	}
}






