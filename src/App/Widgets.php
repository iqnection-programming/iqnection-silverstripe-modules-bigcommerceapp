<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use IQnection\BigCommerceApp\Widgets\Widget;
use IQnection\BigCommerceApp\Widgets\WidgetPlacement;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Injector\Injector;
use IQnection\BigCommerceApp\Widgets\WidgetListItem;
use IQnection\BigCommerceApp\Widgets\WidgetTemplate;
use IQnection\BigCommerceApp\Client;
use BigCommerce\Api\v3\Api\WidgetApi;

class Widgets extends Main
{
	private static $url_segment = '_bc/widgets';
	private static $allowed_actions = [
		'_test' => 'ADMIN',
		'widgetconfig',
		'items',
		'item',
		'add',
		'widgetForm',
		'edit',
		'resync',
		'listItemForm',
		'delete',
		'deleteitem',
		'syncHomePage',
		'placement',
		'deleteplacement',
		'PlacementForm'
	];
	
	private static $url_handlers = [
		'items/$WidgetID/item/$ListItemID' => 'item',
		'items/$WidgetID/deleteitem/$ListItemID' => 'deleteitem',
		'items/$WidgetID/widgetconfig/$ListItemID' => 'widgetconfig'
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
	
	protected $_currentWidget;
	public function currentWidget()
	{
		if (is_null($this->_currentWidget))
		{
			if ($id = $this->getRequest()->param('ID'))
			{
				$this->_currentWidget = Widget::get()->byID($id);
			}
			elseif ($id = $this->getRequest()->param('WidgetID'))
			{
				$this->_currentWidget = Widget::get()->byID($id);
			}
			elseif ($id = $this->getRequest()->postVar('_WidgetID'))
			{
				$this->_currentWidget = Widget::get()->byID($id);
			}
			if (!$this->_currentWidget)
			{
				$this->_currentWidget = Widget::create();
			}
		}
		return $this->_currentWidget;
	}
	
	protected $_currentWidgetListItem;
	public function currentWidgetListItem()
	{
		if (is_null($this->_currentWidgetListItem))
		{
			if ($id = $this->getRequest()->requestVar('_ListItemID'))
			{
				$this->_currentWidgetListItem = WidgetListItem::get()->byID($id);
			}
			elseif ($id = $this->getRequest()->param('ListItemID'))
			{
				$this->_currentWidgetListItem = WidgetListItem::get()->byID($id);
			}
			if (!$this->_currentWidgetListItem)
			{
				$widget = $this->currentWidget();
				if ( ($widget->ListItemClass()) && ($widget = $this->currentWidget()) )
				{
					$this->_currentWidgetListItem = Injector::inst()->create($widget->ListItemClass());
				}
			}
		}
		return $this->_currentWidgetListItem;
	}
	
	public function resync()
	{
		if ($widget = $this->currentWidget())
		{
			$widget->syncObject();
			$this->addAlert('Widget Synced');
		}
		return $this->redirectBack();
	}
	
	public function edit()
	{
		if ($widget = $this->currentWidget())
		{
			$widget->syncObject();
			$this->_currentWidget = $widget;
		}
		return $this;
	}
	
	public function widgetForm()
	{
		$widget = $this->currentWidget();
		$fields = $widget->getFrontEndFields();
		$fields->push( Forms\HiddenField::create('_WidgetID','',$widget->ID) );
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSaveWidget','Save')
		);
		if ($widget->Exists())
		{
			$removeText = ($widget->BigID) ? 'Deactivate' : 'Delete';
			$actions->push(Forms\FormAction::create('doDeleteWidget',$removeText)->addExtraClass('btn-danger ml-2'));
		}
		
		$validator = $widget->getFrontEndRequiredFields($fields);
		
		$form = Forms\Form::create(
			$this,
			'widgetForm',
			$fields,
			$actions,
			$validator
		);
		$form->loadDataFrom($widget);
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSaveWidget($data,$form)
	{
		$widget = $this->currentWidget();
		$form->saveInto($widget);
		try {
			$widget->write();
			$this->addAlert('Widget Saved');
		} catch (\Exception $e) {
			throw $e;
		}
		return $this->redirectBack();
	}
	
	public function doDeleteWidget()
	{
		$widget = $this->currentWidget();
		if ($widget->Exists())
		{
			if ($widget->BigID)
			{
				$widget->Unlink();
				$this->addAlert('Widget Removed from BigCommerce');
			}
			else
			{
				$this->addAlert('Widget Deleted');
				$widget->delete();
			}
		}
		else
		{
			$this->addAlert('Widget Not Found','danger');
		}
		return $this->redirect($this->Link());
	}
	
	public function deleteitem()
	{
		$widget = $this->currentWidget();
		if ($listItem = $this->currentWidgetListItem())
		{
			$listItem->delete();
			$this->addAlert('Item Deleted');
		}
		return $this->redirectBack();	
	}
	
	public function listItemForm()
	{
		$widget = $this->currentWidget();
		if (!$listItem = $this->currentWidgetListItem())
		{
			return null;
		}		
		$fields = $listItem->getFrontEndFields();
		$fields->push( Forms\HiddenField::create('_WidgetID','',$widget->ID));
		if ($listItem->Exists())
		{
			$fields->push( Forms\HiddenField::create('_ListItemID','',$listItem->ID));
		}		
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSaveListItem','Save')
		);
		
		$validator = $listItem->getFrontEndRequiredFields($fields);
		
		$form = Forms\Form::create(
			$this,
			'listItemForm',
			$fields,
			$actions,
			$validator
		);
		$form->loadDataFrom($listItem);
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSaveListItem($data,$form)
	{
		$widget = $this->currentWidget();
		$listItem = $this->currentWidgetListItem();
		$listItem->WidgetID = $widget->ID;
		$form->saveInto($listItem);
		try {
			$listItem->write();
			$this->addAlert('Item Saved!');
		} catch (\Exception $e) {
			throw $e;
		}
		return $this->redirect($this->Link('items/'.$widget->ID));
	}
	
	public function doRemoveListItem($data, $form)
	{
		$listItem = $this->currentWidgetListItem();
		if ($listItem->Exists())
		{
			$listItem->delete();
		}
		return $this->redirectBack();
	}
	
	public function PlacementForm()
	{
		$widget = $this->currentWidget();
		$placement = WidgetPlacement::singleton();
		$fields = $placement->getFrontEndFields();
		$fields->push( Forms\HiddenField::create('_WidgetID','',$widget->ID));
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSavePlacement','Save')
		);
		
		$validator = $placement->getFrontEndRequiredFields($fields);
		
		$form = Forms\Form::create(
			$this,
			'PlacementForm',
			$fields,
			$actions,
			$validator
		);
//		$form->loadDataFrom($placement);
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSavePlacement($data,$form)
	{
		$widget = $this->currentWidget();
		$placement = WidgetPlacement::singleton();
		$placement->WidgetBigID = $widget->BigID;
		$placement->RegionName = $data['Entity'][$data['PageType']]['Region'];
		$placement->EntityID = $data['Entity'][$data['PageType']]['ID'];
		$placement->TemplateFile = $data['PageType'];
		
		try {
			$placement->sync();
			$this->addAlert('Widget Placed');
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(), 'danger');
		}
		return $this->redirect($this->Link('widgetconfig/'.$widget->ID));
	}
	
	public function deleteplacement()
	{
		$BigID = $this->getRequest()->param('ID');
		$inst = WidgetPlacement::singleton();
		$inst->BigID = $BigID;
		$inst->delete();
		return $this->redirectBack();
	}
}






