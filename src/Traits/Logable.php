<?php

namespace IQnection\BigCommerceApp\Traits;

use SilverStripe\Control\Director;

/**
 * Adds methods to a class for easy and consistent logging of data, whatever it might be
 */
trait Logable
{
	protected $debug = false;
	
	public function setDebug($debug = true)
	{
		$this->debug = $debug;
		return $this;
	}
	
	public function getDebug()
	{
		return $this->debug;
	}
	
	public function logEntry($data, $filename, $title = null, $key = null)
	{
		$dir = Director::getAbsFile('bc-logs');
		if (!file_exists($dir))
		{
			mkdir($dir,0777,true);
		}
		$filePath = $dir.'/'.$filename;
		$trace = debug_backtrace(false, 2);
		$debug = "\n".str_repeat('-',30);
		$debug .= "\nFile: ".$trace[1]['file'];
		$debug .= "\nLine: ".$trace[1]['line'];
		$debug .= "\n".date('c');
		$debug .= $key ? "\nKey: ".$key : null;
		$debug .= "\n".$title;
		$debug .= "\nData:";
		$debug .= "\n".print_r($data,1);
		file_put_contents($filePath, $debug, FILE_APPEND);
	}
	
	public function logDebug($data,$title, $key = null)
	{
		if ($this->getDebug())
		{
			$filename = date('Y-m-d').'.log';
			$this->logEntry($data, $filename, $title, $key);
		}
	}
	
	public function logError($data, $title)
	{
		$filename = date('Y-m-d').'.error.log';
		$this->logEntry($data, $filename, $title, $key);
	}
	
	public function logException(\Exception $e)
	{
		$this->logError([
			'message' => $e->getMessage(),
			'exception' => $e->__toString()
		],'---EXCEPTION---');
	}
}

