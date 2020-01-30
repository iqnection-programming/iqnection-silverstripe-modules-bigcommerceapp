<?php

namespace IQnection\BigCommerceApp\Model;


interface ApiObjectInterface
{	
	/**
	 * BigCommerce Model method to remove the object from BigCommerce, but not from our local database
	 */
	public function Unlink();

//	public function Sync();
	
	public function ApiData();
	
	public function loadFromApi($data);
	
}















