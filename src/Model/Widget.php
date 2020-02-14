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

namespace IQnection\BigCommerceApp\Model;

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
use IQnection\BigCommerceApp\Model\WidgetPlacement;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Entities\WidgetPlacementEntity;
use SilverStripe\Core\ClassInfo;
use IQnection\BigCommerceApp\Model\ApiObjectInterface;

class Widget extends DataObject implements ApiObjectInterface
{	
	private static $extensions = [
		ApiObject::class
    ];
	
	private static $table_name = 'BCWidget';
	private static $template_class; // must be an extension of WidgetTemplate::class;
	private static $entity_class = \IQnection\BigCommerceApp\Entities\WidgetEntity::class;
	
	private static $db = [
		'Active' => 'Boolean',
		'Description' => 'Text',
	];
	
	private static $has_many = [
		'Placements' => WidgetPlacement::class
	];
		
	public function getFrontEndFields($params = null)
	{
		$fields = parent::getFrontEndFields($params);
		$fields->dataFieldByName('Description')->setDescription('For internal purposes only');
		$fields->dataFieldByName('Title')->setDescription('Internal Title - Does NOT display in the store');
		if (!$this->Exists())
		{
			// what kind of widget are we creating
			$fields->push( Forms\DropdownField::create('ClassName','Widget Type')
				->setSource(self::getTypes())
				->setEmptyString('-- Select --')
			);
		}
		else
		{
			$fields->push( Forms\TextField::create('_ClassName','Widget Type')
				->setValue($this->singular_name())
				->setAttribute('disabled','disabled')
			);
		}
		$this->extend('updateFrontEndFields', $fields);
		return $fields;
	}
	
	public function getFrontEndRequiredFields(Forms\FieldList &$fields)
	{
		$requiredFields = parent::getFrontEndRequiredFields($fields);
		if (!$this->Exists())
		{
			$fields->dataFieldByName('WidgetType')->addExtraClass('required');
			$requiredFields->addRequiredField('WidgetType'); 
		}
		return $requiredFields;
	}
	
	public function CanAddPlacement($member = null, $context = [])
	{
		if (!$this->BigID)
		{
			return false;
		}
		return true;
	}
	
	public function loadApiData($data)
	{
		if ($data)
		{
			$this->BigID = $data->uuid ? $data->uuid : $data->id;
		}
		else
		{
			$this->BigID = null;
		}
		$this->invokeWithExtensions('updateLoadFromApi',$data);
		return $this;
	}
	
	/**
	 * Provides the widget collections to the dashboard for management
	 * Each collection should be in format:
	 * 	array(
	 *		'ComponentName' => [Component name], 
	 *		'Title' => [Collection Title], 
	 *		'Items' => [Collection Items]
	 *	)
	 * @returns object ArrayList
	 */
	protected $_collections;
	public function Collections()
	{
		if (is_null($this->_collections))
		{
			$this->_collections = ArrayList::create();
		}
		return $this->_collections;
	}
	
	public static function getTypes()
	{
		$widgetTypes = [];
		foreach(ClassInfo::subclassesFor(Widget::class, false) as $key => $widgetType)
		{
			$widgetTypes[$key] = $widgetType::singleton()->singular_name();
		}
		return $widgetTypes;
	}
		
	public function ApiData()
	{
		$data = [
			'uuid' => $this->BigID,
			'name' => $this->Title,
			'description' => $this->Description,
			'widget_template_uuid' => $this->WidgetTemplate()->BigID,
			'widget_configuration' => $this->buildConfiguration()
		];
		$this->invokeWithExtensions('updateApiData', $data);
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
	}
	
	public function buildConfiguration()
	{
		return [];
	}
	
	public function WidgetTemplateClass()
	{
		if ($class = $this->Config()->get('template_class'))
		{
			return $class;
		}
		return false;
		user_error('static::$template_class not declared on class '.get_class($this));
	}
	
	protected $_bcPlacements;
	public function BcPlacements($refresh = false)
	{
		if (is_null($this->_bcPlacements))
		{
			$this->_bcPlacements = ArrayList::create();
			if ($this->BigID)
			{
				$this->_bcPlacements = WidgetPlacementEntity::PlacementsForWidget($this->BigID, $refresh);
			}
		}
		return $this->_bcPlacements;
	}
	
	public function SyncPlacements()
	{
		if ($this->BigID)
		{
			$dbPlacements = $this->Placements();
			$bcPlacements = $this->BcPlacements(true);
			$syncedIDs = [];
			foreach($bcPlacements as $bcPlacement)
			{
				if (!$dbPlacement = $dbPlacements->Find('BigID',$bcPlacement->BigID))
				{
					if (!$dbPlacement = WidgetPlacement::get()->Find('BigID',$bcPlacement->BigID))
					{
						$dbPlacement = WidgetPlacement::create();
					}
				}
				$dbPlacement->loadApiData($bcPlacement);
				$dbPlacement->write();
				$this->Placements()->add($dbPlacement);
				$syncedIDs[] = $dbPlacement->ID;
			}
			$removePlacements = $dbPlacements;
			if (count($syncedIDs))
			{
				$removePlacements = $dbPlacements->Exclude('ID',$syncedIDs);
			}
			foreach($removePlacements as $removePlacement)
			{
				$removePlacement->remove();
			}
		}
		else
		{
			$this->Placements()->removeAll();
		}
		return $this;
	}
		
//	protected $_placements;
//	public function Placements()
//	{
//		if (is_null($this->_placements))
//		{
//			$this->_placements = ArrayList::create();
//			if ($this->BigID)
//			{
//				$this->_placements = WidgetPlacementEntity::PlacementsForWidget($this->BigID);
//			}
//		}
//		return $this->_placements;
//	}
	
	public function validate()
	{
		$result = parent::validate();
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
		}
		if ($this->Exists())
		{
			$this->BigID = null;
			$this->write();
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