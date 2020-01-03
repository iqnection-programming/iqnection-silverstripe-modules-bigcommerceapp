<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\ORM\ArrayList;

class Notification extends DataObject
{
	const STATUS_NEW = 'New';
	const STATUS_VIEWED = 'Viewed';
	const STATUS_DISMISSED = 'Dismissed';
	
	private static $table_name = 'Notification';
	
	private static $db = [
		'Message' => 'Varchar(255)',
		'Status' => "Enum('New,Viewed,Dismissed','New')",
		'Link' => 'Varchar(255)'
	];
	
	private static $has_one = [
		'Member' => Member::class
	];
	
	public static function NotifyAll($message, $link = null)
	{
		$notifications = ArrayList::create();
		foreach(Member::get() as $member)
		{
			$notifications->push(self::Notify($message, $member, $link));
		}
		return $notifications;
	}
	
	public static function Notify($message, $member, $link = null)
	{
		$notification = Notification::create([
			'Message' => $message,
			'MemberID' => $member->ID,
			'Link' => $link,
			'Status' => self::STATUS_NEW
		]);
		$notification->write();
		return $notification;
	}
	
	public function Viewed()
	{
		$this->Status = self::STATUS_VIEWED;
		$this->write();
		return $this;
	}
	
	public function Dismiss($member = null)
	{
		$this->Status = self::STATUS_DISMISSED;
		$this->write();
		return $this;
	}
}



