<?php
/**
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2007-2022 Shoplync Inc
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bike Model Filter
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*/
class sales_order_shipment extends ObjectModel {
 
    /** @var int shipment ID */ 
    public $shipment_id; 
    /** @var int prestashop order ID */ 
    public $ps_order_id; 
    /** @var int sms sales order ID */ 
    public $sales_order_id;
 
    /** @var string carrier name */ 
    public $carrier_name; 
    /** @var string tracking number */ 
    public $tracking_number; 
    /** @var string tracking url */ 
    public $tracking_url;
    /** @var string shipped date */ 
    public $shipped_date;
 
    /**
     * Definition of class parameters     
     */ 
    public static $definition = array( 
        'table' => 'sales_order_shipment', 
        'primary' => 'id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array(
            'shipment_id' => array('type' => self::TYPE_INT),         
            'ps_order_id' => array('type' => self::TYPE_INT),         
            'sales_order_id' => array('type' => self::TYPE_INT),         
            'carrier_name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'tracking_number' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'tracking_url' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'shipped_date' => array('type' => self::TYPE_DATE), 
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'sales_order_shipments',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'sales_order_shipment',  // Detail of an entity 
        'fields' => [] 
    ]; 
}