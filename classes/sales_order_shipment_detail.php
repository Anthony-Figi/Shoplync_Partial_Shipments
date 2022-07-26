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
class sales_order_shipment_detail extends ObjectModel {
 
    /** @var int shipment detail id */ 
    public $shipment_detail_id;
    /** @var int shipment id */ 
    public $shipment_id; 
    /** @var string supplier part number */ 
    public $supplier_part_number;
 
    /** @var string manufacturer part no */ 
    public $mfg_part_number;  
    /** @var string sku */ 
    public $sku; 
    /** @var int shipment quantity */ 
    public $shipment_quantity;
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'sales_order_shipment_detail', 
        'primary' => 'id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array( 
            'shipment_detail_id' => array('type' => self::TYPE_INT), 
            'shipment_id' => array('type' => self::TYPE_INT), 
            'supplier_part_number' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'mfg_part_number' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'sku' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'shipment_quantity' => array('type' => self::TYPE_INT), 
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'sales_order_shipment_details',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'sales_order_shipment_detail',  // Detail of an entity 
        'fields' => [] 
    ]; 
}