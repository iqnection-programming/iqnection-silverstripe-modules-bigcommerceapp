<?php

namespace IQnection\BigCommerceApp\Cron;

use SilverStripe\Control\Controller;
use IQnection\BigCommerceApp\Model\Notification;

class BackgroundJobs extends Sync
{
	private static $segment = 'background-jobs';
	protected $title = 'Background Jobs';
	protected $description = 'Runs jobs scheduled in the background';
	private static $recurring_jobs = [];

	private static $log_dir = 'logs';
	private static $log_file = 'background-jobs.log';

	private static $activate = true;

	public function run($request)
	{
		if (!$this->Config()->get('activate'))
		{
			$this->reportJob('Background Jobs Disabled');
			return;
		}
		if ( ($task = $request->getVar('task')) && (method_exists($this,$task)) )
		{
			$this->{$task}($request);
			return;
		}
		$this->killStuckJobs();
		$this->runNextJob($request);
		$this->checkRecurringJobs($request);
	}

	public function killStuckJobs()
	{
		$runningJobs = BackgroundJob::get()->Filter('Status', BackgroundJob::STATUS_RUNNING);
		$this->reportJob($runningJobs->Count().' Running Jobs');
		foreach($runningJobs as $runningJob)
		{
			if (strtotime($runningJob->LastEdited) < strtotime('-30 minutes'))
			{
				$this->reportJob($runningJob->Name.' created on '.$runningJob->Created.' Killed');
				$runningJob->Status = BackgroundJob::STATUS_FAILED;
				$runningJob->write();
				Notification::NotifyAll('!! Server Job was Killed due to Failure to Complete: [ID:'.$runningJob->ID.'] '.$runningJob->Name);
			}
		}
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
		if ( (is_array($message)) || (is_object($message)) )
		{
			$message = json_encode($message);
		}
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

	public function forceJobRun($request)
	{
		$this->reportJob('Forcing Background Job');
		if ($id = $request->getVar('id'))
		{
			if ($job = BackgroundJob::get()->byID($id))
			{
				$this->reportJob('Running Background Job '.$job->Name);
				$this->runJob($job);
			}
		}
		$this->message('Complete');
	}

	public function runNextJob($request)
	{
		$endTime = strtotime('+3 minute');
		$this->reportJob('Running Background Job');
		$jobs = BackgroundJob::getOpen();
		if ($id = $request->getVar('id'))
		{
			$jobs = $jobs->Filter('ID',$id);
		}
		$this->reportJob($jobs->Count().' Jobs Scheduled');
		foreach($jobs as $job)
		{
			if ($job->Status == BackgroundJob::STATUS_OPEN)
			{
				$this->runJob($job);
				sleep(3);
				if (strtotime('now') > $endTime)
				{
					$this->reportJob('Times up');
					break;
				}
			}
		}
		$this->reportJob('Complete');
		$this->closeStatusReport();
	}

	protected function runJob($job)
	{
		$this->reportJob('Running Job ['.$job->ID.']: '.$job->getTitle());
		try {
			$job->Run();
		} catch (\Exception $e) {
			$this->reportJob($e->getMessage(),'ERROR');
			if (method_exists($e,'getResponseBody'))
			{
				$this->reportJob($e->getResponseBody(),'EXCEPTION');
			}
			Notification::NotifyAll('!! Server Job Failed: [ID:'.$job->ID.'] '.$job->Name);
		}
		return $job;
	}

	public function checkRecurringJobs($request)
	{
		$recurringJobs = $this->Config()->get('recurring_jobs');
		$this->message($recurringJobs,'Recurring Jobs');
		foreach($recurringJobs as $jobName => $recurringJobSpecs)
		{
			$this->message($jobName);
			$CallClass = $recurringJobSpecs['call_class'];
			$CallMethod = $recurringJobSpecs['call_method'];
			$Hours = $recurringJobSpecs['hours'];

//			$this->message($recurringJobSpecs, $jobName);
			if (preg_match('/,/',$Hours))
			{
				$Hours = explode(',',$Hours);
			}
			elseif ($Hours === "*")
			{
				$Hours = [];
				for($h=0;$h<24;$h++)
				{
					$Hours[] = $h;
				}
			}
			if (!is_array($Hours))
			{
				$Hours = [$Hours];
			}
			// is this the proper time to run the job?
			if (!in_array(date('G'), $Hours))
			{
				continue;
			}
			// is there an existing job running or open
			$existing = BackgroundJob::get()->Filter(['Name' => $jobName, 'CallClass' => $CallClass, 'CallMethod' => $CallMethod, 'Status' => [BackgroundJob::STATUS_OPEN, BackgroundJob::STATUS_RUNNING]]);
			if ($existing->Count())
			{
				foreach($existing as $existingJob)
				{
					// make sure the job isn't stuck
					if (strtotime($existingJob->LastEdited) > strtotime('-1 hour'))
					{
						$this->message('Job Already Pending');
						continue;
					}
					$existingJob->Status = BackgroundJob::STATUS_FAILED;
					$existingJob->write();
				}
			}
			// to get jobs to run when usage is low, we can set a time for when the job should be created
			// if we're within the same hour, create the new job
			$hash = md5($jobName.'-'.$CallClass.'-'.$CallMethod.'-'.date('Y-m-d-G'));
			// prevent the job from running multiple times an hour
			$previousRun = BackgroundJob::get()->Find('Hash', $hash);
			if (!$previousRun)
			{
				$this->message('Creating Job');
				BackgroundJob::CreateJob($CallClass, $CallMethod, [], $jobName, $hash);
			}
			else
			{
				$this->message('Job has already been run for this time period');
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








