<?php

namespace IQnection\BigCommerceApp\Cron;

class BackgroundJobs extends Sync
{
	private static $segment = 'background-jobs';
	protected $title = 'Background Jobs';
	protected $description = 'Runs jobs scheduled in the background';
	
	public function run($request)
	{
		$this->runNextJob($request);
	}
	
	public function runNextJob()
	{
		$endTime = strtotime('+3 minute');
		$this->message('Running Background Job');
		$jobs = BackgroundJob::getOpen();
		$this->message($jobs->Count().' Jobs Scheduled');
		foreach($jobs as $job)
		{
			$this->message('Running Job: '.$job->getTitle());
			$job->Run();
			if (strtotime('now') > $endTime)
			{
				$this->message('Times up');
				break;
			}
		}
		$this->message('Complete');
	}
}