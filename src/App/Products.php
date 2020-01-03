<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;

class Products extends Main
{
	private static $url_segment = '_bc/products';
	private static $allowed_actions = [
		'view',
		'edit'
	];
	
	private static $nav_links = [
		'Products' => [
			'path' => '',
			'icon' => 'th-large',
			'children' => [
				'Search' => [
					'path' => 'search',
				],
				'Attached Files' => [
					'path' => 'files'
				]
			]
		]
	];
	
	private static $theme_packages = [
		'forms',
		'datatables'
	];
	
	public function index()
	{
		return $this->Customise([
			'Products' => Widget::get()
		]);
	}
	
	protected $_currentRecord;
	public function currentRecord()
	{
		if (is_null($this->_currentRecord))
		{
			if ($id = $this->getRequest()->param('ID'))
			{
				$this->_currentRecord = Widget::get()->byID($id);
			}
			elseif ($id = $this->getRequest()->postVar('_ID'))
			{
				$this->_currentRecord = Widget::get()->byID($id);
			}
			if (!$this->_currentRecord)
			{
				$this->_currentRecord = Widget::create();
			}
		}
		return $this->_currentRecord;
	}
	
	public function recordForm()
	{
		$widget = $this->currentRecord();
		$fields = $widget->getFrontEndFields();
		
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSave','Save')
		);
		if ($widget->Exists())
		{
			$actions->push(Forms\FormAction::create('doDelete','Delete')->addExtraClass('btn-danger ml-2'));
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
	
	public function doSave($data,$form)
	{
		$record = $this->currentRecord();
		$form->saveInto($record);
		try {
			$record->write();
		} catch (\Exception $e) {
			throw $e;
		}
		return $this->redirectBack();
	}
	
	public function items()
	{
		
	}
	
	
}