<?php
/**
 * Webhook
 *
 * @package  BigCommerce\Api\v3
 */

/**
 * BigCommerce API
 *
 * A Swagger Document for the BigCommmerce v3 API.
 *
 * OpenAPI spec version: 3.0.0b
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace IQnection\BigCommerceApp\Api\Model;

use \ArrayAccess;

class Webhook implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'Webhook';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'id' => 'int',
        'client_id' => 'string',
        'store_hash' => 'string',
        'scope' => 'string',
        'destination' => 'string',
        'headers' => 'string[]',
        'is_active' => 'bool',
        'created_at' => 'int',
        'updated_at' => 'int'
    ];

    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of attributes where the key is the local name, and the value is the original name
     * @var string[]
     */
    protected static $attributeMap = [
        'id' => 'id',
        'client_id' => 'client_id',
        'store_hash' => 'store_hash',
        'scope' => 'scope',
        'destination' => 'destination',
        'headers' => 'headers',
        'is_active' => 'is_active',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'client_id' => 'setClientId',
        'store_hash' => 'setStoreHash',
        'scope' => 'setScope',
        'destination' => 'setDestination',
        'headers' => 'setHeaders',
        'is_active' => 'setIsActive',
        'created_at' => 'setCreatedAt',
        'updated_at' => 'setUpdatedAt'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'client_id' => 'getClientId',
        'store_hash' => 'getStoreHash',
        'scope' => 'getScope',
        'destination' => 'getDestination',
        'headers' => 'getHeaders',
        'is_active' => 'getIsActive',
        'created_at' => 'getCreatedAt',
        'updated_at' => 'getUpdatedAt'
    ];

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    public static function setters()
    {
        return self::$setters;
    }

    public static function getters()
    {
        return self::$getters;
    }

    

    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = [])
    {
        $this->container['id'] = array_key_exists('id', $data) ? $data['id'] : null;
        $this->container['client_id'] = array_key_exists('client_id', $data) ? $data['client_id'] : null;
        $this->container['store_hash'] = array_key_exists('store_hash', $data) ? $data['store_hash'] : null;
        $this->container['scope'] = array_key_exists('scope', $data) ? $data['scope'] : null;
        $this->container['destination'] = array_key_exists('destination', $data) ? $data['destination'] : null;
        $this->container['headers'] = array_key_exists('headers', $data) ? $data['headers'] : null;
        $this->container['is_active'] = array_key_exists('is_active', $data) ? $data['is_active'] : null;
        $this->container['created_at'] = array_key_exists('created_at', $data) ? $data['created_at'] : null;
        $this->container['updated_at'] = array_key_exists('updated_at', $data) ? $data['updated_at'] : null;
    }

    /**
     * returns container
     * @return array
     */
    public function get()
    {
        return $this->container;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = [];
        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properteis are valid
     */
    public function valid()
    {
        $allowed_values = ["default", "async", "defer"];
        return true;
    }


    /**
     * Gets uuid
     * @return string
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets uuid
     * @param string $uuid The primary identifier.
     * @return $this
     */
    public function setId($uuid)
    {
        $this->container['id'] = $uuid;

        return $this;
    }

    /**
     * Gets name
     * @return string
     */
    public function getClientId()
    {
        return $this->container['client_id'];
    }

    /**
     * Sets name
     * @param string $name The user-friendly name.
     * @return $this
     */
    public function setClientId($client_id)
    {
        $this->container['client_id'] = $client_id;

        return $this;
    }

    /**
     * Gets description
     * @return string
     */
    public function getStoreHash()
    {
        return $this->container['store_hash'];
    }

    /**
     * Sets description
     * @param string $description The user-friendly description.
     * @return $this
     */
    public function setStoreHash($store_hash)
    {
        $this->container['store_hash'] = $store_hash;

        return $this;
    }

    /**
     * Gets html
     * @return string
     */
    public function getScope()
    {
        return $this->container['scope'];
    }

    /**
     * Sets html
     * @param string $html An html string containing exactly one `script` tag. Only present if `kind` is `script_tag`
     * @return $this
     */
    public function setScope($scope)
    {
        $this->container['scope'] = $scope;

        return $this;
    }

    /**
     * Gets src
     * @return string
     */
    public function getDestination()
    {
        return $this->container['destination'];
    }

    /**
     * Sets src
     * @param string $src The `src` attribute of the script to load. Only present if `kind` is `src`.
     * @return $this
     */
    public function setDestination($destination)
    {
        $this->container['destination'] = $destination;

        return $this;
    }

    /**
     * Gets auto_uninstall
     * @return bool
     */
    public function getHeaders()
    {
        return $this->container['headers'];
    }

    /**
     * Sets auto_uninstall
     * @param bool $auto_uninstall Whether to uninstall this script when the app associated with it is removed.
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->container['headers'] = $headers;

        return $this;
    }

    /**
     * Gets load_method
     * @return string
     */
    public function getIsActive()
    {
        return $this->container['is_active'];
    }

    /**
     * Sets load_method
     * @param string $load_method The load method to use for the script. Values are `default`, `async`, or `defer`.
     * @return $this
     */
    public function setIsActive($is_active)
    {
        $this->container['is_active'] = $is_active;

        return $this;
    }

    /**
     * Gets location
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->container['created_at'];
    }

    /**
     * Sets location
     * @param string $location Where on the page to place the script. Values are `head` or `footer`.
     * @return $this
     */
    public function setCreatedAt($location)
    {
        $this->container['created_at'] = $created_at;

        return $this;
    }

    /**
     * Gets visibility
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->container['updated_at'];
    }

    /**
     * Sets visibility
     * @param string $visibility Which set of pages the script should load on. The values allowed for this parameter are `storefront`, `all_pages`, `checkout` and `order_confirmation`. Please note that you need to have `Checkout content` scope to use `all_pages` and `checkout`.
     * @return $this
     */
    public function setUpdatedAt($visibility)
    {
        $this->container['updated_at'] = $updated_at;

        return $this;
    }

    
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(\BigCommerce\Api\v3\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        }

        return json_encode(\BigCommerce\Api\v3\ObjectSerializer::sanitizeForSerialization($this));
    }
}


