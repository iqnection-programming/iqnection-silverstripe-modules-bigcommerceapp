<?php

namespace IQnection\BigCommerceApp\Model;

use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Model\ApiObjectInterface;
use IQnection\BigCommerceApp\Model\Category;
use IQnection\BigCommerceApp\Extensions\HasMetafields;

class Product extends DataObject implements ApiObjectInterface
{
	use \IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $entity_class = \IQnection\BigCommerceApp\Entities\ProductEntity::class;
	
	private static $table_name = 'BCProduct';
	
	private static $extensions = [
		ApiObject::class,
		HasMetafields::class
    ];
	
	private static $db = [
		'position' => 'Int(11)',
		'sku' => 'Varchar(255)',
	];
	
	private static $belongs_many_many = [
		'Categories' => Category::class
	];
	
	private static $default_sort = 'position ASC';
	
	private static $readonly_fields = [
		'position',
		'sku'
	];
	
	
	public function ApiData() 
	{
		$categoryIDs = array_filter($this->Categories()->Column('BigID'));
		$data = [
			'name' => $this->Title,
			'sku' => $this->sku,
			'categories' => $categoryIDs,
		];
		if ($this->BigID)
		{
			$data['id'] = $this->BigID;
		}
		$this->extend('updateApiData',$data);
		return $data;
	}
	
	public function Unlink() {}
	
	public function loadFromApi($data)
	{
		if ($data)
		{
			$this->BigID = $data->id;
			$this->Title = $data->name;
			$this->sku = $data->sku;
			$this->position = $data->position;
			if (is_array($data->categories))
			{
				$existingCategoryIDs = $this->Categories()->Column('BigID');
				$diff1 = array_diff($existingCategoryIDs, $data->categories);
				$diff2 = array_diff($data->categories,$existingCategoryIDs);
				if ( (count($diff1)) || (count($diff2)) )
				{
					$remove = $this->Categories()->Exclude('BigID',$data->categories);
					if ($remove->Count())
					{
						$this->Categories()->removeMany($remove->Column('ID'));
					}
					$add = Category::get()->Filter('BigID',$data->categories);
					if ($add->Count())
					{
						$this->Categories()->addMany($add->Column('ID'));
					}
				}
			}
			$this->LastSynced = date('Y-m-d H:i:s');
		}
		else
		{
			$this->BigID = null;
		}
		$this->RawData = json_encode($data);
		return $this;
	}
}