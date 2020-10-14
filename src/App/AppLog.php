<?php

namespace IQnection\BigCommerceApp\App;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType;
use SilverStripe\Assets\File;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Cron\BackgroundJob;
use SilverStripe\ORM\PaginatedList;

class AppLogs extends Main
{
	private static $managed_class = BackgroundJob::class;
	private static $url_segment = '_bc/logs';
	private static $hidden = true;
	
	private static $allowed_actions = [
		'view',
		'remove'
	];
	
	private static $nav_links = [
		'Logs' => [
			'path' => '',
			'icon' => 'info-circle'
		]
	];

//	protected $_CurrentLog;
//	public function CurrentLog()
//	{
//		if (is_null($this->_CurrentLog))
//		{
//			$this->_CurrentLog = ArrayData::create([]);
//			if ($hash = $this->getRequest()->param('ID'))
//			{
//				$filesystem = $this->getLogFilesystem();
//				foreach($this->getLogs() as $file)
//				{
//					if ($file->hash == $hash)
//					{
//						$file->filedata = $filesystem->read($file->path);
//						$this->_CurrentLog = $file;
//						break;
//					}
//				}
//			}
//		}	
//		return $this->_CurrentLog;
//	}
//	
//	public function init()
//	{
//		parent::init();
//	}
//	
//	protected $_LogFilesystem;
//	public function getLogFilesystem()
//	{
//		if (is_null($this->_LogFilesystem))
//		{
//			$dir = Director::getAbsFile('bc-logs');
//			$adapter = new Local($dir);
//			$this->_LogFilesystem = new Filesystem($adapter);
//		}
//		return $this->_LogFilesystem;
//	}
//	
//	public function getLogs()
//	{
//		$filesystem = $this->getLogFilesystem();
//		$files = $filesystem->listContents('/',true);
//		foreach($files as &$file)
//		{
//			$file['hash'] = md5(json_encode($file));
//			$file['datetime'] = FieldType\DBField::create_field(FieldType\DBDatetime::class,$file['timestamp']);
//			$file['filesize'] = File::format_size($file['size']);
//		}
//		return ArrayList::create($files)->Sort('timestamp','DESC');
//	}
//	
	public function index()
	{
		$jobs = BackgroundJob::get()->Sort('ID','DESC');
		if ($filters = $this->getRequest()->requestVar('Filters'))
		{
			$jobs = $jobs->Filter($filters);
		}
		$jobs = PaginatedList::create($jobs);
		$jobs->setRequest($this->getRequest());
		$jobs->setPageLength(250);
		return $this->Customise([
			'Logs' => $jobs,
			'Filters' => ArrayData::create($filters ? $filters : [])
		]);
	}
	
//	public function remove()
//	{
//		if ($file = $this->CurrentLog())
//		{
//			$filesystem = $this->getLogFilesystem();
//			if ($filesystem->delete($file->path))
//			{
//				$this->addAlert('File Removed');
//			}
//			else
//			{
//				$this->addAlert('There was an error deleting the log file','danger');
//			}
//		}
//		return $this->redirectBack();
//	}
}



