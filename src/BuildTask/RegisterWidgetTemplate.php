<?php

namespace IQnection\BigCommerceApp\BuildTask;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use IQnection\BigCommerceApp\Model\WidgetTemplate;
use IQnection\BigCommerceApp\Entities\WidgetTemplateEntity;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Convert;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class RegisterWidgetTemplate extends BuildTask
{
	protected $title = 'Register BigCommerce Widget Template';
	
	protected $description = 'Registers a Widget Template with BigCommerce for use when creating widgets.';
	
	private static $segment = 'register-bc-widget-template';
	
	public function run($request)
	{
		if ($sync = $request->getVar('syncAll'))
		{
			$this->syncAll($sync);
			return;
		}
		if ($sync = $request->getVar('preview'))
		{
			$this->previewTemplate($sync);
			return;
		}
		if ($sync = $request->getVar('validate'))
		{
			$this->validateTemplate($sync);
			return;
		}
		if ($sync = $request->getVar('sync'))
		{
			$this->syncTemplate($sync);
			return;
		}
		if ($delete = $request->getVar('delete'))
		{
			$this->deleteTemplate($delete);
		}
		// go through all subclasses of WidgetTemplate and make sure there is a record in teh DB, and it's been registered with BigCommerce
		$subclasses = ClassInfo::subclassesFor(WidgetTemplate::class, false);
		if (!count($subclasses))
		{
			$this->message('There are no widget template classes to register','ERROR');
			return;
		}
		foreach($subclasses as $subclass)
		{
			$singleton = Injector::inst()->get($subclass);
			print '<strong>'.$singleton->getTitle().'</strong>';
			$registeredTemplate = $subclass::get()->Sort('BigID','DESC')->First();
			if ( ($registeredTemplate) && ($registeredTemplate->BigID) )
			{
				print '<div>BigCommerce ID: '.$registeredTemplate->BigID.'</div>';
				print '<div><a href="?validate='.base64_encode($subclass).'" target="_blank">Validate</a></div>';
				print '<div><a href="?preview='.base64_encode($subclass).'" target="_blank">Preview</a></div>';
				print '<div><a href="?sync='.base64_encode($subclass).'">Update</a></div>';
			}
			else
			{
				print '<div><a href="?validate='.base64_encode($subclass).'" target="_blank">Validate</a></div>';
				print '<div><a href="?preview='.base64_encode($subclass).'" target="_blank">Preview</a></div>';
				print '<div><a href="?sync='.base64_encode($subclass).'">Register</a></div>';
			}
		}
		
		print '<h3><a href="?syncAll=1">Sync All Templates from BigCommerce</a></h3>';
		
		print '<h3>Registered Widget Templates</h3><table>';
		foreach($this->getBCWidgetTemplates() as $widgetTemplate)
		{
			print '<tr>';
				print '<td>'.$widgetTemplate->uuid.'</td>';
				print '<td>'.$widgetTemplate->name.'</td>';
				print '<td>'.$widgetTemplate->date_created.'</td>';
				print '<td>';
				if ($dbWidgetTemplate = WidgetTemplate::get()->Find('BigID',$widgetTemplate->uuid))
				{
					print 'Database ID: '.$dbWidgetTemplate->ID;
				}
				print '</td>';
				print '<td><a href="?delete='.$widgetTemplate->uuid.'">DELETE</a></td>';
			print '</tr>';
		}
		print '</table>';
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
	
	protected function validateTemplate($class)
	{
		$class = base64_decode($class);
		$this->message('Validating Widget Template: '.$class);
		$className = ClassInfo::class_name($class);
		if (!class_exists($className))
		{
			$this->message('Class ['.$class.'] Not Found');
		}		
		// get the current version stage
		$currentStage = Versioned::get_stage();
		Versioned::set_stage(Versioned::LIVE);
		$singleton = Injector::inst()->create($className);
		
		$errors = $singleton->validateHandlebars();
		if ($currentStage != Versioned::LIVE)
		{
			Versioned::set_stage($currentStage);
		}
		$this->message($errors,'Results');
	}
	
	protected function previewTemplate($class)
	{
		$class = base64_decode($class);
		$this->message('Widget Template Preview: '.$class);
		$className = ClassInfo::class_name($class);
		if (!class_exists($className))
		{
			$this->message('Class ['.$class.'] Not Found');
		}		
		// get the current version stage
		$currentStage = Versioned::get_stage();
		Versioned::set_stage(Versioned::LIVE);
		$singleton = Injector::inst()->create($className);
		
		print '<pre><xmp>';
		print $singleton->getTemplateHtml();
		print '</xmp></pre>';
		if ($currentStage != Versioned::LIVE)
		{
			Versioned::set_stage($currentStage);
		}
	}
	
	protected function deleteTemplate($uuid)
	{
		$this->message('Deleting Widget Template: '.$uuid);
		$entity = Injector::inst()->create(\IQnection\BigCommerceApp\Entities\WidgetTemplateEntity::class);
		$entity->uuid = $uuid;
		try {
			$entity->Unlink();
			$this->message('Deleted');
		} catch (\Exception $e) {
			$this->message('ERROR');
			$this->message($e->getMessage());
		}
	}
	
	protected function syncAll()
	{
		$bcTemplates = WidgetTemplateEntity::getAll(true);
		$this->message($bcTemplates->Count().' BC Templates Found');
		$dbTemplates = WidgetTemplate::get();
		$this->message($dbTemplates->Count().' DB Templates Found');
		$syncedIDs = [];
		foreach($bcTemplates as $bcTemplate)
		{
			if ($bcTemplate->kind == 'custom')
			{
				$this->message('Checking '.$bcTemplate->name);
				if (!$dbTemplate = $dbTemplates->Find('BigID',$bcTemplate->uuid))
				{
					if (!$dbTemplate = $dbTemplates->Find('Title', $bcTemplate->name))
					{
						$this->message('Creating: '.$bcTemplate->name);
						$dbTemplate = WidgetTemplate::create();
					}
					$dbTemplate->loadApiData($bcTemplate);
					$dbTemplate->write();
				}
				$syncedIDs[] = $dbTemplate->ID;
			}
		}
		$removed = 0;
		foreach($dbTemplates->Exclude('ID',$syncedIDs) as $removeTemplate)
		{
			$this->message($removeTemplate->Title.' no longer exists - removing');
			$removeTemplate->BigID = null;
			$removeTemplate->delete();
			$removed++;
		}
		$this->message(($removed + count($syncedIDs)).' Templates Synced');
	}
	
	protected function syncTemplate($class)
	{
		$class = base64_decode($class);
		$className = ClassInfo::class_name($class);
		if (!class_exists($className))
		{
			$this->message('Class ['.$class.'] Not Found');
		}		
		// get the current version stage
		$currentStage = Versioned::get_stage();
		Versioned::set_stage(Versioned::LIVE);
		$singleton = Injector::inst()->create($className);
		$this->message('Syncing '.$singleton->getTitle());
		if (!$dbObject = $className::get()->Sort('BigID','DESC')->First())
		{
			$this->message('No record exists - creating new record');
			$dbObject = $singleton;
		}
		elseif ($dbObject->BigID)
		{
			$this->message('Record exists - updating uuid: '.$dbObject->BigID);
		}
		try {
			$this->message('Writing object');
			$entity = $dbObject->Sync();
			$dbObject->loadApiData($entity);
			$dbObject->forceChange(true);
			$dbObject->write();
		} catch (\Exception $e) {
			$this->message($e->getMessage(),'Exception');
			$this->message($e->getTraceAsString(), 'Trace');
		}
		if ($currentStage != Versioned::LIVE)
		{
			Versioned::set_stage($currentStage);
		}
	}
	
	protected function message($message, $title = null)
	{
		if (Director::is_cli())
		{
			return $this->cliMessage($message,$title);
		}
		print "<div>";
		if ($title)
		{
			print '<div>'.str_repeat('-',20).'</div>';
			print "<div><strong>".$title."<strong></div>";
		}
		if (is_null($message))
		{
			print 'Value: NULL';
		}
		elseif (is_bool($message))
		{
			print 'Value: '.($message ? 'TRUE' : 'FALSE');
		}
		elseif ( (is_array($message)) || (is_object($message)) )
		{
			print '<pre><xmp>';
			print_r($message);
			print '</xmp></pre>';
		}
		else
		{
			print $message;
		}
		print '</div>';
	}
	
	protected function cliMessage($message, $title = null)
	{
		print "\n";
		if ($title)
		{
			print "----".$title."----\n";
		}
		if (is_null($message))
		{
			print 'Value: NULL';
		}
		elseif (is_bool($message))
		{
			print 'Value: '.($message ? 'TRUE' : 'FALSE');
		}
		else
		{
			print_r($message);
		}
	}
}