<?php

namespace IQnection\BigCommerceApp\App;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType;
use SilverStripe\Assets\File;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class FileManager extends Main
{
	private static $url_segment = 'admin/assets';
	
	private static $allowed_actions = [];
	
	private static $nav_links = [
		'File Manager' => [
			'path' => '',
			'icon' => 'images',
			'target' => '_blank'
		]
	];
	
	public function index()
	{
		return $this->redirect('/admin/assets/');
	}
}



