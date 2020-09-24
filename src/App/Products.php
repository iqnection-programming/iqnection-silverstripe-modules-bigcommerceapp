<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\PaginatedList;
use IQnection\BigCommerceApp\Model\Product;
use SilverStripe\View\Requirements;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Cron\BackgroundJob;

class Products extends Main
{
	private static $managed_class = Product::class;
	private static $url_segment = '_bc/products';
	private static $allowed_actions = [
		'edit',
		'resync',
		'search',
		'relation'
	];
	
	private static $nav_links = [
		'Products' => [
			'path' => '',
			'icon' => 'th-large'
		]
	];
	
	private static $theme_packages = [
		'forms',
		'datatables'
	];
	
	public function index()
	{
		Requirements::customScript(
<<<JS
(function($){
"use strict";
$(document).ready(function(){
	$("#product-list").dataTable({
		"processing": true,
		"serverSide": true,
		"ordering": false,
		"pageLength": 100,
		"deferRender": true,
		"searchDelay": 750,
		"columns": [
			{ "data": "ID", "searchable": false },
			{ "data": "BigID" },
			{ "data": "Title" },
			{ "data": "SKU" },
			{ "data": "Created", "searchable": false },
			{ 	"data": "Actions", 
				"className": "text-right text-nowrap", 
				"orderable": false, 
				"searchable": false,
				"createdCell": function(td, cellData, rowData, row, col) {
					return $(td).html('<a href="{$this->Link('edit')}'+(rowData.ID)+'" class="btn btn-primary btn-sm">Edit</a>');
				}
			}
		],
		"ajax": "{$this->Link('search_api')}"
	});
});
}(jQuery));
JS
);
		return $this->searchProducts(null, 'products');
	}
	
	public function SyncStatus()
	{
		$job = BackgroundJob::get()->Filter(['Name' => 'sync_products'])->Exclude('Status',BackgroundJob::STATUS_FAILED)->Sort('CompleteDate','DESC')->First();
		return $job;
	}
}








