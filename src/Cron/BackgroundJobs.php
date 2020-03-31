<?php

namespace IQnection\BigCommerceApp\Cron;

use SilverStripe\Control\Controller;

class BackgroundJobs extends Sync
{
	private static $segment = 'background-jobs';
	protected $title = 'Background Jobs';
	protected $description = 'Runs jobs scheduled in the background';
	private static $recurring_jobs = [];
	
	private static $log_dir = 'logs';
	private static $log_file = 'background-jobs.log';
	
	public function run($request)
	{
		$this->runNextJob($request);
	}
	
	public function logFilePath()
	{
		return Controller::join_links($this->logFileDir(),$this->Config()->get('log_file'));
	}
	
	public function logFileDir()
	{
		return Controller::join_links(BASE_PATH,$this->Config()->get('log_dir'));
	}
	
	public function logFile()
	{
		$logDir = $this->logFileDir();
		if (!file_exists($logDir))
		{
			mkdir($logDir, 0777, true);
		}
		$logPath = $this->logFilePath();
		$logs = null;
		if (file_exists($logPath))
		{
			$logs = file_get_contents($logPath);
		}
		return $logs;	
	}
	
	protected $_statusReport;
	protected function openStatusReport()
	{
		if (is_null($this->_statusReport))
		{
			$this->_statusReport = $this->logFile();
		}
		return $this->_statusReport;
	}
	
	protected function reportJob($message)
	{
		$this->message($message);
		$this->openStatusReport();
		$this->_statusReport .= "\n".$message;
		file_put_contents($this->logFilePath(), $this->_statusReport);
		return $this->_statusReport;
	}
	
	protected function closeStatusReport()
	{
		if (!is_null($this->_statusReport))
		{
			// remove double line breaks
			$this->_statusReport = preg_replace('/(\n|\r){2,}/',"\n",$this->_statusReport);
			// truncate from the top to a max size
			$lines = explode("\n",$this->_statusReport);
			$lines = array_reverse($lines);
			$chunks = array_chunk($lines, 500);
			$chunk = array_shift($chunks);
			$lines = array_reverse($lines);
			$this->_statusReport = implode("\n",$lines);
			file_put_contents($this->logFilePath(), $this->_statusReport);
		}
	}
	
	public function runNextJob()
	{
		$endTime = strtotime('+3 minute');
		$this->reportJob('Running Background Job');
		$jobs = BackgroundJob::getOpen();
		$this->reportJob($jobs->Count().' Jobs Scheduled');
		foreach($jobs as $job)
		{
			$this->reportJob('Running Job: '.$job->getTitle());
			try {
				$job->Run();
			} catch (\Exception $e) {
				$job->reportJob($e->getMessage(),'ERROR');
				if (method_exists($e,'getResponseBody'))
				{
					$job->reportJob($e->getResponseBody(),'EXCEPTION');
				}
			}
			if (strtotime('now') > $endTime)
			{
				$this->reportJob('Times up');
				break;
			}
		}
		$this->checkRecurringJobs();
		$this->reportJob('Complete');
		$this->closeStatusReport();
	}
	
	public function checkRecurringJobs()
	{
		foreach($this->Config()->get('recurring_jobs') as $jobName => $recurringJobSpecs)
		{
			$CallClass = $recurringJobSpecs['call_class'];
			$CallMethod = $recurringJobSpecs['call_method'];
			$Hours = $recurringJobSpecs['hours'];
//			$this->message($recurringJobSpecs, $jobName);
			if (preg_match('/,/',$Hours))
			{
				$Hours = explode(',',$Hours);
			}
			if (!is_array($Hours))
			{
				$Hours = [$Hours];
			}
			if (BackgroundJob::get()->Filter(['Name' => $jobName, 'CallClass' => $CallClass, 'CallMethod' => $CallMethod, 'Status' => [BackgroundJob::STATUS_OPEN, BackgroundJob::STATUS_RUNNING]])->Count())
			{
//				$this->message('Job Already Pending');
				return;
			}
			// to get jobs to run when usage is low, we can set a time for when the job should be created
			// if we're within the same hour, create the new job
			if (in_array(date('G'), $Hours))
			{
				// prevent the job from running multiple times an hour
				$hourBuffer = (24 / count($Hours)) - 1;
				$previousRun = BackgroundJob::get()->Filter(['Name' => $jobName, 'CallClass' => $CallClass, 'CallMethod' => $CallMethod])->Sort('CompleteDate','DESC')->First();
				if ( (!$previousRun) || (strtotime($previousRun->CompleteDate) < strtotime('-'.$hourBuffer.' hours')) )
				{
					BackgroundJob::CreateJob($CallClass, $CallMethod);
				}
			}
		}
	}
	
	public function setupCleaner()
	{
		if (BackgroundJob::get()->Filter(['CallClass' => static::class, 'CallMethod' => 'clean', 'Status' => [BackgroundJob::STATUS_OPEN, BackgroundJob::STATUS_RUNNING]])->Count())
		{
			return;
		}
		$lastCleaned = BackgroundJob::get()->Filter(['CallClass' => static::class, 'CallMethod' => 'clean'])->Sort('CompleteDate','DESC')->First();
		if ( (!$lastCleaned) || ( (!$lastCleaned->dbObject('CompleteDate')->IsToday()) && ($lastCleaned->dbObject('CompleteDate')->InPast()) ) )
		{
			BackgroundJob::CreateJob(static::class, 'clean');
		}
	}
	
	public function clean()
	{
		foreach(BackgroundJob::get()->Exclude('Status', [BackgroundJob::STATUS_OPEN, BackgroundJob::STATUS_RUNNING])->Filter('CompleteDate:LessThan', date('Y-m-d H:i:s', strtotime('-7 days'))) as $oldJob)
		{
			$oldJob->delete();
		}
	}
}








