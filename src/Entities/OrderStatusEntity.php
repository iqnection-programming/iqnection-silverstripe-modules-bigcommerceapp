<?php

namespace IQnection\BigCommerceApp\Entities;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Injector\Injector;

/**
 * 0	Incomplete	An incomplete order happens when a shopper reached the payment page, but did not complete the transaction.
 * 1	Pending	Customer started the checkout process, but did not complete it.
 * 2	Shipped	Order has been shipped, but receipt has not been confirmed; seller has used the Ship Items action.
 * 3	Partially Shipped	Only some items in the order have been shipped, due to some products being pre-order only or other reasons.
 * 4	Refunded	Seller has used the Refund action.
 * 5	Cancelled	Seller has cancelled an order, due to a stock inconsistency or other reasons.
 * 6	Declined	Seller has marked the order as declined for lack of manual payment, or other reasons.
 * 7	Awaiting Payment	Customer has completed checkout process, but payment has yet to be confirmed.
 * 8	Awaiting Pickup	Order has been pulled, and is awaiting customer pickup from a seller-specified location.
 * 9	Awaiting Shipment	Order has been pulled and packaged, and is awaiting collection from a shipping provider.
 * 10	Completed	Client has paid for their digital product and their file(s) are available for download.
 * 11	Awaiting Fulfillment	Customer has completed the checkout process and payment has been confirmed.
 * 12	Manual Verification Required	Order on hold while some aspect needs to be manually confirmed.
 * 13	Disputed	Customer has initiated a dispute resolution process for the PayPal transaction that paid for the order.
 * 14	Partially Refunded	Seller has partially refunded the order.
*/
class OrderStatusEntity extends Entity
{
	use \IQnection\BigCommerceApp\Traits\Entity;
	
	private static $client_class = \IQnection\BigCommerceApp\ClientV2::class;
	private static $api_class = \IQnection\BigCommerceApp\ClientV2::class;
	private static $cache_name = 'bigcommerce-order-status';
	
	public static function getAll($refresh = false)
	{
		$cacheName = self::generateCacheKey(self::Config()->get('cache_name'));
		$cachedData = self::fromCache($cacheName);
		if ( (!self::isCached($cacheName)) || (!$cachedData) || ($refresh) )
		{
			$cachedData = ArrayList::create();
			$inst = self::singleton();
			$apiClient = $inst->ApiClient();
			$apiDatas = $apiClient->getOrderStatuses();
			foreach($apiDatas as $apiData)
			{
				$newInst = Injector::inst()->create(static::class, []);
				$newInst->loadApiData((array) $apiData->getUpdateFields());
				$cachedData->push($newInst);
			}
			self::toCache($cacheName, $cachedData);
		}
		return $cachedData;
	}
	
	public static function getOrderStatus($status_id)
	{
		$inst = Injector::inst()->create(static::class, []);
		$apiClient = $inst->ApiClient();
		$apiResponse = $apiClient->getOrderStatus($status_id);
		$apiData = $apiResponse->getUpdateFields();
		$inst->loadApiData((array) $apiData);
		return $inst;
	}
}












