<?php

namespace IQnection\BigCommerceApp\BuildTask;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use IQnection\BigCommerceApp\Model\WidgetTemplate;
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
				print '<div><a href="?sync='.preg_replace('/\\\\/','-',$subclass).'">Update</a></div>';
			}
			else
			{
				print '<div><a href="?sync='.preg_replace('/\\\\/','-',$subclass).'">Register</a></div>';
			}
		}
		
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
	
	protected function deleteTemplate($uuid)
	{
		$this->message('Deleting Widget Template: '.$uuid);
		$entity = Injector::inst()->create(\IQnection\BigCommerceApp\Entities\WidgetTemplateEntity::class);
		$entity->uuid = $uuid;
		try {
			$entity->delete();
			$this->message('Deleted');
		} catch (\Exception $e) {
			$this->message('ERROR');
			$this->message($e->getMessage());
		}
	}
	
	protected function syncTemplate($class)
	{
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
			$dbObject->loadFromApi($entity);
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
			print '<pre>';
			print_r($message);
			print '</pre>';
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