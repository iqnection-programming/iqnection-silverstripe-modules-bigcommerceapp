<?php

/**
	A BigCommerce Widget is an injected dataset that is pushed through the API and displays on teh page as though it's native content
	Each widget uses a template, a set of list items, and one or more placements
	In this implementation, a widget template is the "root container"
	Create a new widget template, set a name, assign the SilverStripe template to use, and the WidgetListItem subclass for the data collection
	When saved, the template will be pushed to BigCommerce. Now you can create widgets for this template. 
	Each widget record get's a collection of items. If a different collection is needed, a new widget must be created, which can use the same widget template
	When the widget is configured, set one or more placements to display the widget on the particular page
	
*/

namespace IQnection\BigCommerceApp\Widgets;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms;
use BigCommerce\Api\v3\Model\PlacementRequest;
use BigCommerce\Api\v3\Model\WidgetRequest;
use BigCommerce\Api\v3\Model\WidgetTemplateRequest;
use IQnection\BigCommerceApp\Client;
use IQnection\BigCommerceApp\Model\BigCommerceLog as BcLog;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\SSViewer;
use IQnection\BigCommerceApp\Model\ApiObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Widgets\WidgetPlacement;

class Widget extends ApiObject
{	
	use \IQnection\BigCommerceApp\Traits\ApiModel;

	private static $client_class = \BigCommerce\Api\v3\Api\WidgetApi::class;
	
	private static $table_name = 'BCWidget';
	private static $list_item_class = WidgetListItem::class;
	private static $template_class = WidgetTemplate::class;
	
	private static $db = [
		'Description' => 'Text',
	];
	
	private static $has_many = [
		'ListItems' => WidgetListItem::class
	];
	
	public function getFrontEndFields($params = null)
	{
		$fields = parent::getFrontEndFields($params);
		return $fields;
	}
		
	public function ApiData()
	{
		$data = [
			'name' => $this->Title,
			'description' => $this->Description,
			'widget_template_uuid' => $this->WidgetTemplate()->BigID,
			'widget_configuration' => $this->buildConfiguration()
		];
		return $data;
	}
	
	public function WidgetTemplate()
	{
		if ($templateClass = $this->WidgetTemplateClass())
		{
			foreach(WidgetTemplate::get() as $widgetTemplate)
			{
				if (get_class($widgetTemplate) == $templateClass)
				{
					return $widgetTemplate;
				}
			}
			user_error($templateClass.' Not registered with BigCommerce API');
		}
		user_error('static::$template_class not declared on class '.get_class($this));
	}
	
	public function buildConfiguration()
	{
		$listItems = [];
		foreach($this->ListItems() as $listItem)
		{
			$listItems[] = $listItem->WidgetData();
		}
		return [
			'list_items' => $listItems
		];
	}
	
	public function WidgetTemplateClass()
	{
		return $this->Config()->get('template_class');
	}
	
	public function ListItemClass()
	{
		return $this->Config()->get('list_item_class');
	}
	
	protected $_placements;
	public function Placements()
	{
		if (is_null($this->_placements))
		{
			try {
				$this->_placements = WidgetPlacement::PlacementsForWidget($this->BigID);
			} catch (\Exception $e) {
				
			}
		}
		return $this->_placements;
	}
	
	protected static $_is_pushing = false;
	public function sync($data)
	{
		try {
			$Client = $this->ApiClient();
			$request = new WidgetRequest( $data );
			BcLog::info('Widget Data for: '.$this->BigID, $request);
			if (trim($this->BigID))
			{
				$widget = $Client->updateWidget($this->BigID, $request )->getData();
				BcLog::info('Updated Widget', $this->BigID);
			}
			else
			{
				$widget = $Client->createWidget( $request )->getData();	
				$this->BigID = $widget->getUuid();			
				BcLog::info('Created Widget ',$widget);
			}
			return $widget;
		} catch (\Exception $e) {
			BcLog::error('Exception syncing widget', $e->__toString());
			return false;
		}
	}
	
	public function validate()
	{
		$result = parent::validate();
		if (!$this->Template()->Exists())
		{
//			$result->addFieldError('TemplateID','Please select a template');
		}
		return $result;
	}
	
	public function Unlink()
	{
		if ($this->BigID)
		{
			try {
				$Client = $this->ApiClient();
				$Client->deleteWidget($this->BigID);
				BcLog::info('Deleted Widget', $this->BigID);
			} catch (\Exception $e) {
				BcLog::error('Delete Error', $e->getMessage());
				throw new \SilverStripe\ORM\ValidationException('Error saving widget: '.$e->getMessage());
			}
			if ($this->Exists())
			{
				$this->BigID = null;
				$this->write();
			}
		}
	}
	
	public function onBeforeDelete()
	{
		parent::onBeforeDelete();
		if ($this->BigID)
		{
			try {
				$Client = $this->ApiClient();
				$Client->deleteWidget($this->BigID);
				BcLog::info('Deleted Widget', $this->BigID);
			} catch (\Exception $e) {
				BcLog::error('Delete Error', $e->getMessage());
				throw new \SilverStripe\ORM\ValidationException('Error saving widget: '.$e->getMessage());
			}
		}
	}
}