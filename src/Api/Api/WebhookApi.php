<?php
/**
 * WebhookApi
 *
 * @package  BigCommerce\Api\v3
 */

/**
 * Copied from Modern Tribes BigCommerce SDK. This API was not in the package
 *
 */


namespace IQnection\BigCommerceApp\Api\Api;

use \BigCommerce\Api\v3\Configuration;
use \BigCommerce\Api\v3\ApiClient;
use \BigCommerce\Api\v3\ApiException;
use \BigCommerce\Api\v3\ObjectSerializer;

class WebhookApi
{

    /**
     * API Client
     *
     * @var \BigCommerce\Api\v3\ApiClient instance of the ApiClient
     */
    protected $apiClient;

    /**
     * Constructor
     *
     * @param \BigCommerce\Api\v3\ApiClient $apiClient The api client to use
     */
    public function __construct(\BigCommerce\Api\v3\ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
    * Get API client
    *
    * @return \BigCommerce\Api\v3\ApiClient get the API client
    */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
    * Set the API client
    *
    * @param \BigCommerce\Api\v3\ApiClient $apiClient set the API client
    *
    * @return WebhookApi
    */
    public function setApiClient(\BigCommerce\Api\v3\ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        return $this;
    }

    /**
     * Operation createWebhook
     * Creates a webhook.
     *
     *
     * @param \BigCommerce\Api\v3\Model\WebhookPost $webhook_body  (required)
     * @param array $params = []
     * @return \BigCommerce\Api\v3\Model\WebhookResponse
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function createWebhook($webhook_body, array $params = [])
    {
        list($response) = $this->createWebhookWithHttpInfo( $webhook_body, $params);
        return $response;
    }


    /**
     * Operation createWebhookWithHttpInfo
     *
     * @see self::createWebhook()
     * @param \BigCommerce\Api\v3\Model\WebhookPost $webhook_body  (required)
     * @param array $params = []
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \BigCommerce\Api\v3\Model\WebhookResponse, HTTP status code, HTTP response headers (array of strings)
     */
    public function createWebhookWithHttpInfo( $webhook_body, array $params = [])
    {
        
        // verify the required parameter 'webhook_body' is set
        if (!isset($webhook_body)) {
            throw new \InvalidArgumentException('Missing the required parameter $webhook_body when calling createWebhook');
        }
        

        // parse inputs
        $resourcePath = "/hooks";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(['application/json']);

        // query params
        foreach ( $params as $key => $param ) {
            $queryParams[ $key ] = $this->apiClient->getSerializer()->toQueryValue( $param );
        }

        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // body params
        $_tempBody = null;
        if (isset($webhook_body)) {
        $_tempBody = $webhook_body;
        }
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'POST',
                $queryParams,
                $httpBody,
                $headerParams,
                '\IQnection\BigCommerceApp\Api\Model\WebhookResponse',
                '/hooks'
            );
            return [$this->apiClient->getSerializer()->deserialize($response, '\IQnection\BigCommerceApp\Api\Model\WebhookResponse', $httpHeader), $statusCode, $httpHeader];

         } catch (ApiException $e) {
            switch ($e->getCode()) {
            
                case 200:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\IQnection\BigCommerceApp\Api\Model\WebhookResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 422:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
            }

            throw $e;
        }
    }
    /**
     * Operation deleteWebhook
     * Deletes a webhook.
     *
     *
     * @param string $id The identifier for a specific webhook. (required)
     * @param array $params = []
     * @return null
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function deleteWebhook($id, array $params = [])
    {
        list($response) = $this->deleteWebhookWithHttpInfo($id, $params);
        return $response;
    }


    /**
     * Operation deleteWebhookWithHttpInfo
     *
     * @see self::deleteWebhook()
     * @param string $id The identifier for a specific webhook. (required)
     * @param array $params = []
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function deleteWebhookWithHttpInfo($id, array $params = [])
    {
        
        // verify the required parameter 'id' is set
        if (!isset($id)) {
            throw new \InvalidArgumentException('Missing the required parameter $id when calling deleteWebhook');
        }
        

        // parse inputs
        $resourcePath = "/hooks/{id}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(['application/json']);

        // query params
        foreach ( $params as $key => $param ) {
            $queryParams[ $key ] = $this->apiClient->getSerializer()->toQueryValue( $param );
        }

        // path params


        if (isset($id)) {
            $resourcePath = str_replace(
                "{" . "id" . "}",
                $this->apiClient->getSerializer()->toPathValue($id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'DELETE',
                $queryParams,
                $httpBody,
                $headerParams,
                null,
                '/hooks/{id}'
            );
            return [null, $statusCode, $httpHeader];

         } catch (ApiException $e) {
            switch ($e->getCode()) {
            
                case 404:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 422:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
            }

            throw $e;
        }
    }
    /**
     * Operation getWebhook
     * Gets a webhook.
     *
     *
     * @param string $id The identifier for a specific webhook. (required)
     * @param array $params = []
     * @return \BigCommerce\Api\v3\Model\WebhookResponse
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function getWebhook($id, array $params = [])
    {
        list($response) = $this->getWebhookWithHttpInfo($id, $params);
        return $response;
    }


    /**
     * Operation getWebhookWithHttpInfo
     *
     * @see self::getWebhook()
     * @param string $id The identifier for a specific webhook. (required)
     * @param array $params = []
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \BigCommerce\Api\v3\Model\WebhookResponse, HTTP status code, HTTP response headers (array of strings)
     */
    public function getWebhookWithHttpInfo($id, array $params = [])
    {
        
        // verify the required parameter 'id' is set
        if (!isset($id)) {
            throw new \InvalidArgumentException('Missing the required parameter $id when calling getWebhook');
        }
        

        // parse inputs
        $resourcePath = "/hooks/{id}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(['application/json']);

        // query params
        foreach ( $params as $key => $param ) {
            $queryParams[ $key ] = $this->apiClient->getSerializer()->toQueryValue( $param );
        }

        // path params


        if (isset($id)) {
            $resourcePath = str_replace(
                "{" . "id" . "}",
                $this->apiClient->getSerializer()->toPathValue($id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'GET',
                $queryParams,
                $httpBody,
                $headerParams,
                '\IQnection\BigCommerceApp\Api\Model\WebhookResponse',
                '/hooks/{id}'
            );
            return [$this->apiClient->getSerializer()->deserialize($response, '\IQnection\BigCommerceApp\Api\Model\WebhookResponse', $httpHeader), $statusCode, $httpHeader];

         } catch (ApiException $e) {
            switch ($e->getCode()) {
            
                case 200:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\IQnection\BigCommerceApp\Api\Model\WebhookResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 404:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 422:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
            }

            throw $e;
        }
    }
    /**
     * Operation getWebhooks
     * Gets all webhooks.
     *
     *
     * @param array $params = []
     *     - page int Specifies the page number in a limited (paginated) list of products. (optional)
     *     - limit int Controls the number of items per page in a limited (paginated) list of products. (optional)
     *     - sort string Webhooks field name to sort by. (optional)
     *     - direction string Sort direction. Acceptable values are: &#x60;asc&#x60;, &#x60;desc&#x60;. (optional)
     * @return \BigCommerce\Api\v3\Model\WebhooksResponse
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function getWebhooks(array $params = [])
    {
        list($response) = $this->getWebhooksWithHttpInfo($params);
        return $response;
    }


    /**
     * Operation getWebhooksWithHttpInfo
     *
     * @see self::getWebhooks()
     * @param array $params = []
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \BigCommerce\Api\v3\Model\WebhooksResponse, HTTP status code, HTTP response headers (array of strings)
     */
    public function getWebhooksWithHttpInfo(array $params = [])
    {
        

        // parse inputs
        $resourcePath = "/hooks";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(['application/json']);

        // query params
        foreach ( $params as $key => $param ) {
            $queryParams[ $key ] = $this->apiClient->getSerializer()->toQueryValue( $param );
        }

        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'GET',
                $queryParams,
                $httpBody,
                $headerParams,
                '\IQnection\BigCommerceApp\Api\Model\WebhooksCollectionResponse',
                '/hooks'
            );
            return [$this->apiClient->getSerializer()->deserialize((object) ['data' => $response], '\IQnection\BigCommerceApp\Api\Model\WebhooksCollectionResponse', $httpHeader), $statusCode, $httpHeader];

         } catch (ApiException $e) {
            switch ($e->getCode()) {
            
                case 200:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\IQnection\BigCommerceApp\Api\Model\WebhooksCollectionResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 422:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
            }

            throw $e;
        }
    }
    /**
     * Operation updateWebhook
     * Updates a webhook.
     *
     *
     * @param string $id The identifier for a specific webhook. (required)
     * @param \BigCommerce\Api\v3\Model\WebhookPut $webhook_body  (required)
     * @param array $params = []
     * @return \BigCommerce\Api\v3\Model\WebhookResponse
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function updateWebhook($id, $webhook_body, array $params = [])
    {
        list($response) = $this->updateWebhookWithHttpInfo($id,  $webhook_body, $params);
        return $response;
    }


    /**
     * Operation updateWebhookWithHttpInfo
     *
     * @see self::updateWebhook()
     * @param string $id The identifier for a specific webhook. (required)
     * @param \BigCommerce\Api\v3\Model\WebhookPut $webhook_body  (required)
     * @param array $params = []
     * @throws \BigCommerce\Api\v3\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \BigCommerce\Api\v3\Model\WebhookResponse, HTTP status code, HTTP response headers (array of strings)
     */
    public function updateWebhookWithHttpInfo($id,  $webhook_body, array $params = [])
    {
        
        // verify the required parameter 'id' is set
        if (!isset($id)) {
            throw new \InvalidArgumentException('Missing the required parameter $id when calling updateWebhook');
        }
        
        // verify the required parameter 'webhook_body' is set
        if (!isset($webhook_body)) {
            throw new \InvalidArgumentException('Missing the required parameter $webhook_body when calling updateWebhook');
        }
        

        // parse inputs
        $resourcePath = "/hooks/{id}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(['application/json']);

        // query params
        foreach ( $params as $key => $param ) {
            $queryParams[ $key ] = $this->apiClient->getSerializer()->toQueryValue( $param );
        }

        // path params


        if (isset($id)) {
            $resourcePath = str_replace(
                "{" . "id" . "}",
                $this->apiClient->getSerializer()->toPathValue($id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // body params
        $_tempBody = null;
        if (isset($webhook_body)) {
        $_tempBody = $webhook_body;
        }
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'PUT',
                $queryParams,
                $httpBody,
                $headerParams,
                '\IQnection\BigCommerceApp\Api\Model\WebhookResponse',
                '/hooks/{id}'
            );
            return [$this->apiClient->getSerializer()->deserialize($response, '\IQnection\BigCommerceApp\Api\Model\WebhookResponse', $httpHeader), $statusCode, $httpHeader];

         } catch (ApiException $e) {
            switch ($e->getCode()) {
            
                case 200:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\IQnection\BigCommerceApp\Api\Model\WebhookResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 404:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
                case 422:
                $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\ErrorResponse', $e->getResponseHeaders());
                $e->setResponseObject($data);
                break;
            
            }

            throw $e;
        }
    }
}
