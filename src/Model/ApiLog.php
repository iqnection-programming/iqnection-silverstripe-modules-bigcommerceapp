<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class BigCommerceLog extends DataObject
{
	private static $table_name = 'BigCommerceLog';
	
	private static $db = [
		'Title' => 'Varchar(255)',
		'Request' => 'Text',
		'Entry' => 'Text',
		'Status' => "Enum('info,error','info')"
	];
	
	private static $has_one = [
		'Member' => Member::class
	];
	
	private static $summary_fields = [
		'ID' => 'ID',
		'Created' => 'Date',
		'Title' => 'Title',
		'Status' => 'Status'
	];
	
	private static $default_sort = 'ID DESC';
	
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->removeByName(['Entry','Request']);
		$fields->addFieldToTab('Root.Request', \IQnection\Forms\RawDataField::create('_Request',$this->Request));
		$fields->addFieldToTab('Root.Entry', \IQnection\Forms\RawDataField::create('_Entry',$this->Entry));
		return $fields;
	}
	
	public function CanEdit($member = null, $context = [])
	{
		return false;
	}
	
	public function onBeforeWrite()
	{
		parent::onBeforeWrite();
		$this->Request = json_encode($_REQUEST);
		if ($member = Security::getCurrentUser())
		{
			$this->MemberID = $member->ID;
		}
	}
	
	public static function LogEntry($title, $entry = null, $status = 'info')
	{
		if ( (is_array($entry)) || (is_object($entry)) )
		{
			$entry = json_encode($entry);
		}
		elseif (is_null($entry))
		{
			$entry = '[ NULL ]';
		}
		elseif (is_bool($entry))
		{
			$entry = ($entry) ? '[ TRUE ]' : '[ FALSE ]';
		}
		$log = BigCommerceLog::create();
		$log->Title = $title;
		$log->Entry = print_r($entry,1);
		$log->Status = $status;
		$log->write();
		return $log;
	}
	
	public static function exception($title, \Exception $e)
	{
		$entry = [];
		if (method_exists($e, 'getResponseBody'))
		{
			$entry['Response Body'] = $e->getResponseBody();
		}
		$entry['message'] = $e->getMessage();
		$entry['trace'] = $e->getTraceAsString();
		return self::error($title, $entry);
	}
	
	public static function error($title, $entry)
	{
		return self::LogEntry($title,$entry,'error');
	}
	
	public static function info($title, $entry)
	{
		return self::LogEntry($title,$entry,'info');
	}
}