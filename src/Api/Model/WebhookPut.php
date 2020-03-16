<?php
/**
 * WebhookPut
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

class WebhookPut implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'WebhookPut';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'scope' => 'string',
		'destination' => 'string',
		'headers' => 'string[]',
		'is_active' => 'bool',
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
        'scope' => 'scope',
		'destination' => 'destination',
		'headers' => 'headers',
		'is_active' => 'is_active',
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'scope' => 'setScope',
        'destination' => 'setDestination',
        'headers' => 'setHeaders',
        'is_active' => 'setIsActive',
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'scope' => 'getScope',
        'destination' => 'getDestination',
        'headers' => 'getHeaders',
        'is_active' => 'getIsActive',
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
        $this->container['scope'] = array_key_exists('scope', $data) ? $data['scope'] : null;
        $this->container['destination'] = array_key_exists('destination', $data) ? $data['destination'] : null;
        $this->container['headers'] = array_key_exists('headers', $data) ? (object) $data['headers'] : (object) [];
        $this->container['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : false;
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
        if ($this->container['scope'] === null) {
            $invalid_properties[] = "'scope' can't be null";
        }
        if (strlen($this->container['destination']) > 255) {
            $invalid_properties[] = "invalid value for 'destination', the character length must be smaller than or equal to 255.";
        }
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
        if ($this->container['scope'] === null) {
            return false;
        }
        if (!is_array($this->container['headers'])) {
            return false;
        }
        return true;
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


