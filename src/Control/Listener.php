<?php

namespace IQnection\BigCommerceApp\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use IQnection\BigCommerceApp\Cron\BackgroundJob;
use IQnection\BigCommerceApp\Entities\WebhookEntity;

class Listener extends Controller
{
	private static $url_segment = '_hook';
	private static $allowed_actions = [];
	
	
	/**
	 * register actions to webhooks
	 * expects array keys to be the webhook scope
	 * should be a string of the class name and method
	 * eg. Category::Pull
	 */
	private static $registry = [];
	
	public function init()
	{
		parent::init();
		$this->logHook(json_decode($this->getRequest()->getBody()),'Body');
	}
		
	public function index()
	{
		if (!$body = json_decode($this->getRequest()->getBody()))
		{
			return 'Nothing to see here';
		}
		if (!$this->validateHook($body))
		{
			$this->logHook('Invalid Post');
		}
		$scope = $body->scope;
		$registry = $this->Config()->get('registry');
		if (array_key_exists($scope, $registry))
		{
			foreach($registry[$scope] as $call)
			{
				list($className, $method) = explode('::',$call);
				// only certain events are monitored
				$job = BackgroundJob::CreateJob($className, $method, $body, $body->hash);
			}
		}
		return $this->getResponse()->setBody(true);
	}
	
	public function Link($action = null)
	{
		return Controller::join_links($this->Config()->get('url_segment'),$action);
	}
	
	public function AbsoluteLink($action = null)
	{
		return Director::absoluteURL($this->Link($action));
	}
	
	protected function logHook($data, $title = null)
	{
		$entry = str_repeat('-',50)."\n";
		if ($title)
		{
			$entry .= '**** '.$title." ****\n";
		}
		$entry .= 'Timestamp: '.date('c')."\n";
		$entry .= print_r($data,1)."\n";
		file_put_contents(BASE_PATH.'/webhook.log', $entry, FILE_APPEND);
	}
	
	protected function validateHook($body)
	{
		if (is_string($body))
		{
			$body = json_decode($body,1);
		}
		if (is_object($body))
		{
			$body = (array) $body;
		}
		return ( (WebhookEntity::Config()->get('app_id') == $body['headers']['app_id']) || (WebhookEntity::Config()->get('app_id') == $body['headers']['app-id']) );
	}	
}












