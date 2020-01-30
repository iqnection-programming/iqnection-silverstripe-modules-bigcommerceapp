<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;
use IQnection\BigCommerceApp\Model\Category;

class Categories extends Main
{
	private static $url_segment = '_bc/categories';
	private static $allowed_actions = [
		'view',
		'edit',
		'sync',
		'pull',
		'recordForm',
		'relation',
		'relatedObjectForm',
		'relationremove'
	];
	
	private static $nav_links = [
		'Categories' => [
			'path' => '',
			'icon' => 'th-large'
		]
	];
	
	private static $theme_packages = [
		'forms',
	];
	
	private static $url_handlers = [
		'edit/$ID/relationremove/$ComponentName!/$RelatedID' => 'relationremove',
		'edit/$ID/relation/$ComponentName!/$RelatedID' => 'relation'
	];
	
	public function index()
	{
		return $this->Customise([
			'Categories' => Category::get()
		]);
	}

	public function pull()
	{
		if (!$record = $this->currentRecord())
		{
			$this->addAlert('Record not found','warning');
		}
		else
		{
			try {
				$record->SyncFromApi();
				$this->addAlert('Data Updated');
			} catch (\Exception $e) {
				$this->addAlert($e->getMessage(),'danger');
			}
		}
		return $this->redirectBack();
	}
	
	public function relatedObject()
	{
		$record = $this->currentRecord();
		$ComponentName = $this->getRequest()->param('ComponentName') ? $this->getRequest()->param('ComponentName') : $this->getRequest()->requestVar('ComponentName');
		if ( (!$ComponentName) || (!$componentClass = $record->getRelationClass($ComponentName)) )
		{
			return 'No relation found';
		}
		$components = $record->{$ComponentName}();
		$objectID = $this->getRequest()->param('RelatedID') ? $this->getRequest()->param('RelatedID') : $this->getRequest()->param('RelatedID');
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
		if ( ( (!$relatedObject) || (!$relatedObject->Exists()) ) && (!$relatedObject->CanCreate()) )
		{
			return 'You do not have permission to add this record';
		}
		if ( ($relatedObject) && ($relatedObject->Exists()) && (!$relatedObject->CanEdit()) )
		{
			return 'You do not have permission to edit this record';
		}

		$fields = $relatedObject->getFrontEndFields();
		
		$fields->push( Forms\HiddenField::create('_ID','')->setValue($record->ID) );
		$ComponentName = $this->getRequest()->param('ComponentName') ? $this->getRequest()->param('ComponentName') : $this->getRequest()->requestVar('ComponentName');
		$fields->dataFieldByName('ComponentName')->setValue($ComponentName);
		
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
		$record->NeedsSync = true;
		$record->write();
		$this->addAlert($component->singular_name().' Saved');
		return $this->redirect($this->Link('edit/'.$record->ID));
	}
	
	public function doDeleteComponent($data,$form)
	{
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
		
	public function relation()
	{
		return $this;
	}
	
	protected $_currentRecord;
	public function currentRecord()
	{
		if (is_null($this->_currentRecord))
		{
			if ($id = $this->getRequest()->param('ID'))
			{
				$this->_currentRecord = Category::get()->byID($id);
			}
			elseif ($id = $this->getRequest()->requestVar('_ID'))
			{
				$this->_currentRecord = Category::get()->byID($id);
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
			$actions->push(Forms\FormAction::create('doDelete','Delete')->addExtraClass('btn-danger ml-2'));
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
				$message .= ' & Synced';
			}
			$this->addAlert($message);
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
			throw $e;
		}
		return $this->redirectBack();
	}
}









