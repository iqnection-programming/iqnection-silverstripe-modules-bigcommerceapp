<?php

namespace IQnection\BigCommerceApp\Cron;

use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\ClassInfo;

class BackgroundJob extends DataObject
{
	const STATUS_OPEN = 'open';
	const STATUS_RUNNING = 'running';
	const STATUS_COMPLETE = 'complete';
	const STATUS_FAILED = 'failed';
	
	private static $table_name = 'BCBackgroundJob';
	
	private static $db = [
		'CallClass' => 'Varchar(255)',
		'CallMethod' => 'Varchar(255)',
		'Args' => 'Text',
		'Status' => "Enum('open,running,complete,failed','open')",
		'CompleteDate' => 'Datetime',
		'Hash' => 'Varchar(255)',
		'Logs' => 'Text'
	];
	
	private static $defaults = [
		'Status' => 'open'
	];
	
	public function getTitle()
	{
		return $this->CallClass.'::'.$this->CallMethod;
	}
	
	public static function CreateJob($class, $method, $args = [], $hash = null)
	{
		if (!$hash)
		{
			$hash = md5(json_encode([$class, $method, $args]));
		}
		if ($existing = self::get()->Filter(['Hash' => $hash, 'Status' => ['open','running']])->First())
		{
			return $existing;
		}
		$job = new self;
		$job->CallClass = $class;
		$job->CallMethod = $method;
		$job->Args = json_encode($args);
		$job->Status = self::STATUS_OPEN;
		$job->Hash = $hash;
		$job->write();
		return $job;
	}
	
	public function Run()
	{
		$this->Status = self::STATUS_RUNNING;
		$this->write();
		try {
			// no methods should be static, create an instance of the class
			$className = ClassInfo::class_name($this->CallClass);
			$args = json_decode($this->Args,1);
			$inst = Injector::inst()->create($className);
			// if we're provided an ID, get teh specific model
			if ($inst instanceof DataObject)
			{
				if ( (isset($args['BigID'])) && ($dbInst = $className::get()->Find('BigID',$args['BigID'])) )
				{
					$inst = $dbInst;
				}
				elseif ( (isset($args['ID'])) && ($dbInst = $className::get()->byID($args['ID'])) )
				{
					$inst = $dbInst;
				}
			}
			if (!ClassInfo::hasMethod($inst, $this->CallMethod))
			{
				throw new \Exception('Method '.$this->CallMethod.' does not exist in class '.$className,500);
			}
			
			ob_start();
			$result = call_user_func_array([$inst, $this->CallMethod], $args);
			$this->Logs .= "\nOutput:\n".ob_get_contents();
			ob_end_clean();
			$this->Status = self::STATUS_COMPLETE;
		} catch (\Exception $e) {
			$this->Logs .= "\n".$e->getMessage()."\n\n".$e->getTraceAsString();
			$this->Status = self::STATUS_FAILED;
		}
		$this->CompleteDate = date('Y-m-d H:i:s');
		$this->write();
		return $this;
	}
	
	public static function RunJob($job)
	{
		return $job->Run();
	}
	
	public static function NextJob()
	{
		return BackgroundJob::get()->Filter('Status', self::STATUS_OPEN)->Sort('Created','ASC')->First();
	}
	
	public static function getOpen()
	{
		return BackgroundJob::get()->Filter('Status', self::STATUS_OPEN)->Sort('Created','ASC');
	}
}