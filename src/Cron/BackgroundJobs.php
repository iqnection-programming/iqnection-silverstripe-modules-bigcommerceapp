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
		$this->setupCleaner();
		$this->message('Complete');
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








