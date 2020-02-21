<?php


namespace IQnection\BigCommerceApp\App;

use SilverStripe\Core\Extension;
use SilverStripe\Forms;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\PaginatedList;
use IQnection\BigCommerceApp\Model\Product;
use SilverStripe\View\Requirements;

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
			{ "data": "ID" },
			{ "data": "BigID" },
			{ "data": "Title" },
			{ "data": "SKU" },
			{ "data": "Created" },
			{ 	"data": "Actions", 
				"className": "text-right text-nowrap", 
				"orderable": false, 
				"searchable": false,
				"createdCell": function(td, cellData, rowData, row, col) {
					return $(td).html('<a href="{$this->Link('edit')}/'+(rowData.ID)+'" class="btn btn-primary btn-sm">Edit</a>'+
								'<a href="{$this->Link('resync')}/'+(rowData.ID)+'" class="btn btn-outline-success btn-sm">Resync</a>');
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
	
	public function search()
	{
		user_error(__FUNCTION__.' in '.__CLASS__.' is Deprecated');	
		$products = Product::get();
		$recordsTotal = $products->Count();

		$search = $this->getRequest()->requestVar('search');
		if (trim($search['value']))
		{
			$products = $products->FilterAny([
				'sku:PartialMatch' => trim($search['value']),
				'Title:PartialMatch' => trim($search['value']),
			]);
		}
		if ($orders = $this->getRequest()->requestVar('order'))
		{
			$cols = ['ID','BigID','Title','SKU','Created'];
			foreach($orders as $order)
			{
				$col = $cols[$order['column']];
				$dir = $order['dir'];
				$products = $products->Sort($col,$dir);
			}
		}
		
		$finalProductsTotal = $products->Count();
		$limit = $this->getRequest()->requestVar('length') ? $this->getRequest()->requestVar('length') : 100;
		$start = 0;
		if ($this->getRequest()->requestVar('start'))
		{
			$start = $this->getRequest()->requestVar('start');
		}
		$products = $products->Limit($limit,$start);
		
		
		if ($this->getRequest()->isAjax())
		{
			$ajaxData = [
				'data' => [],
				'draw' => strtotime('now'),
				'recordsTotal' => $recordsTotal,
				'recordsFiltered' => $finalProductsTotal,
			];
			foreach($products as $product)
			{
				$ajaxData['data'][] = [
					'ID' => $product->ID,
					'BigID' => $product->BigID,
					'Title' => $product->Title,
					'SKU' => $product->sku,
					'Created' => $product->dbObject('Created')->Nice(),
					'Actions' => '<a href="'.$this->Link('edit/'.$product->ID).'" class="btn btn-primary btn-sm">Edit</a>'.
								'<a href="'.$this->Link('resync/'.$product->ID).'" class="btn btn-outline-success btn-sm">Resync</a>'
				];
			}
			header('Content-Type: application/json');
			print json_encode($ajaxData);
			die();
		}
			
		
		return $this->Customise([
			'Products' => $products
		]);
	}
	
	
	
}