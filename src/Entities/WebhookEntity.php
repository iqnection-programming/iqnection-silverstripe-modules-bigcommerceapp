<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\DataObject;
use IQnection\BigCommerceApp\Client;
use SilverStripe\Core\Injector\Injector;

class WebhookEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity,
		\IQnection\BigCommerceApp\Traits\Cacheable;
	
	private static $client_class = \BigCommerce\Api\v3\Api\WebhookApi::class;
	private static $cache_name = 'bigcommerce-webhooks';
	private static $additional_headers = [
		'app_id' => 'law89ycrnl8arlaram93ruvc4mu4'
	];
	
	private static $scopes = [
		'Order' => [
			'store/order/*' => 'All Order Events',
			'store/order/created' => 'Order Created',
			'store/order/updated' => 'Order Changed',
			'store/order/archived' => 'Order Archived',
			'store/order/statusUpdated' => 'Order Status Changed',
			'store/order/message/created' => 'New Order Message'
		],
		'Product' => [
			'store/product/*' => 'All Product Events',
			'store/product/deleted' => 'Product Deleted',
			'store/product/created' => 'Product Created',
			'store/product/updated' => 'Product Changed',
			'store/product/inventory/updated' => 'Product Inventory Changed',
			'store/product/inventory/order/updated' => 'Product Inventory Changed (including orders)'
		],
		'Category' => [
			'store/category/*' => 'All Category Events',
			'store/category/created' => 'Category Created',
			'store/category/updated' => 'Category Changed',
			'store/category/deleted' => 'Category Deleted'
		],
		'SKU' => [
			'store/sku/*' => 'All SKU Events',
			'store/sku/created' => 'SKU Created',
			'store/sku/updated' => 'SKU Changed',
			'store/sku/deleted' => 'SKU Deleted',
			'store/sku/inventory/updated' => 'SKU Inventory Changed',
			'store/sku/inventory/order/updated' => 'SKU Inventory Changed (including orders)'
		],
		'Customer' => [
			'store/customer/*' => 'All Customer Events',
			'store/customer/created' => 'Customer Created',
			'store/customer/updated' => 'Customer Changed',//	Customer is updated. Does not currently track changes to the customer address.
			'store/customer/deleted' => 'Customer Deleted',
			'store/customer/address/created' => 'Customer Address Created',
			'store/customer/address/updated' => 'Customer Address Changed',
			'store/customer/address/deleted' => 'Customer Address Deleted',
			'store/customer/payment/instrument/default/updated' => 'Customer Default Payment Method Changed'
		],
		'Cart' => [
			'store/cart/*' => 'All Cart Events',	//	Subscribe to all cart events. This will also subscribe you to cart/lineItem.
			'store/cart/created' => 'Cart Created', //	This webhook will fire whenever a new cart is created either via a storefront shopper adding their first item to the cart or when a new cart being created via an API consumer. If it is from the storefront, then it fires when the first product is added to a new session.(The cart did not exist before) For the API it means a POST to /carts, (V3 and Storefront API). The store/cart/updated will also fire.
			'store/cart/updated' => 'Cart Changed', //	This webhook is fired whenever a cart is modified through the changes in its line items. Eg. when a new item is added to a cart or an existing item’s quantity is updated. This hook also fires when the email is changed during guest checkout or an existing item is deleted. The payload will include the ID of the cart being updated.
												//	This webhook is also fired along with cart created, because the first product being added to an empty cart triggers an update.
												//	- Logging into customer account after creating a cart (email is inherited from customer account email)
												//	- Entering email address via guest checkout
												//	-Changing the email in guest checkout
			'store/cart/deleted' => 'Cart Deleted', //	This webhook will fire whenever a cart is deleted. This will occur either when all items have been removed from a cart and it is auto-deleted, or when the cart is explicitly removed via a DELETE request by an API consumer. This ends the lifecycle of the cart. The store/cart/updated webhook will also fire when the last item is removed.
			'store/cart/couponApplied' => 'Coupon Applied', //	This webhook will fire whenever a new coupon code is applied to a cart. It will include the ID of the coupon code
			'store/cart/abandoned' => 'Cart Abandoned', //	This webhook will fire once after a cart is abandoned. A cart is considered abandoned if no changes were made at least one hour after the last modified property.
			'store/cart/converted' => 'Cart Converted' //	This hook fires when a cart is converted into an order, which is typically after the payment step of checkout on the storefront. At this point, the Cart is no longer accessible and has been deleted. This hook returns both the Cart ID and Order ID for correlation purposes.
		],
		'Cart Line Item' => [
			'store/cart/lineItem/*' => 'All Cart Line Item Events', //	Subscribe to all cart line item events. This webhook will fire when a change occurs to line items in the cart. This can be items added to a cart, removed or updated.(Ex. change to quantity, product options or price).
			'store/cart/lineItem/created' => 'Cart Line Item Created', //	When a new item is added to the cart
			'store/cart/lineItem/updated' => 'Cart Line Item Changed', //	When an item’s quantity has changed or the product options change.
			'store/cart/lineItem/deleted' => 'Cart Line Item Deleted' //	When an item is deleted from the cart
		],
		'Shipment' => [
			'store/shipment/*' => 'All Shipment Events', //	Subscribe to all store/shipment events
			'store/shipment/created' => 'Shipment Created', //	Shipment is created
			'store/shipment/updated' => 'Shipment Changed', //	Shipment is updated
			'store/shipment/deleted' => 'Shipment Deleted' //	Shipment is deleted
		],
		'Subscriber' => [
			'store/subscriber/*' => 'All Subscriber Events', //	Subscribe to all store/subscriber events
			'store/subscriber/created' => 'Subscriber Created', //	Subscriber is created
			'store/subscriber/updated' => 'Subscriber Changed', //	Subscriber is updated
			'store/subscriber/deleted' => 'Subscriber Deleted', //	Subscriber is deleted
		]
	];
	
	public function getTitle()
	{
		if ($scope = $this->scope)
		{
			foreach($this->Config()->get('scopes') as $scopeCategory)
			{
				if (array_key_exists($scope, $scopeCategory))
				{
					return $scopeCategory[$scope];
				}
			}
		}
		return parent::getTitle();
	}
	
	public function ApiData()
	{
		$data = parent::ApiData();
		if (!is_array($data['headers']))
		{
			$data['headers'] = [];
		}
		$data['headers'] = array_merge($data['headers'], $this->Config()->get('additional_headers'));
		return $data;
	}
	
	public function Sync() 
	{
		$apiClient = $this->ApiClient();
		$data = $this->ApiData();
		$id = $data['id'] ? $data['id'] : ($data['BigID'] ? $data['BigID'] : null);
		if ($id)
		{
			$response = $apiClient->updateWebhook($id, new \BigCommerce\Api\v3\Model\WebhookPut($data));
		}
		else
		{
			$response = $apiClient->createWebhook(new \BigCommerce\Api\v3\Model\WebhookPost($data));
		}
		$this->loadApiData($response->getData());
		return $this;
	}
	
	public function delete()
	{
		if ( (!$id = $this->id) && (!$id = $this->BigID) )
		{
			throw new \Exception('ID not provided');
		}
		$apiClient = $this->ApiClient();
		return $apiClient->deleteWebhook($id);
	}
			
	public static function getAll($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$page = 1;
			$apiDataResponse = $apiClient->getWebhooks();
			$apiDatas = $apiDataResponse->getData();
			foreach($apiDatas as $apiData)
			{
				$newInst = Injector::inst()->create(static::class, []);
				$newInst->loadApiData($apiData);
				$cachedData->push($newInst);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function getById($id, $refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__.$id);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$inst = Injector::inst()->create(static::class, []);
			$apiClient = $inst->ApiClient();
			$apiResponse = $apiClient->getWebhookById($id);
			$apiRecords = $apiResponse->getData();
			$inst->loadApiData($apiRecord);
			self::toCache($cacheName, $inst);
		}
		return $cachedData;
	}
	
	public static function forDropdown()
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name').__FUNCTION__);
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = [];
			foreach(self::getAll() as $bcRecord)
			{
				$cachedData[$bcRecord->id] = $bcRecord->name;
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public function ApiClient()
	{
		$client = parent::ApiClient();
		$config = $client->getApiClient()->getConfig();
		$host = $config->getHost();
		$host = preg_replace('/\/v3/','/v2',$host);
		$config->setHost($host);
		return $client;
	}
}









