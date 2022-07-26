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

/**
 * Class itself
 */
class shoplync_partial_shipmentsqueryModuleFrontController extends ModuleFrontController
{   
    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }

    /**
     * This function sets the appropritate error headers and returns the default 'Failed' error response
     * 
     * $errorMessage string - The error message to return
     * $extra_details array() - array of key:value pairs to be added to the error json response
     * 
    */
    public function setErrorHeaders($errorMessage = 'Failed', $extra_details = [])
    {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=UTF-8');
        
        $error_array = ['errorResponse' => $errorMessage];
        
        if(!empty($extra_details) && is_array($extra_details))
            $error_array = $error_array + $extra_details;
        
        $this->ajaxDie(json_encode($error_array));
    }

    /**
    * Triggered via an AJAX call, Retrieves the products vehicle fitment given a particular make
    *
    * $_POST['make_id'] int - Used to filter product vehicle fitments by make
    * $_POST['product_id'] int - Specifies which product to retrieve vehicle fitment for
    * $_POST['attribute_id'] int- Specifies whether to retrieve fitment for a product combination
    */ 
    public function displayAjaxGetShipmentDetails()
    {
        if (Tools::isSubmit('shipment_id'))
        {
            $shipment_id = Tools::getValue('shipment_id');
            
            $fitments = $this->GetShipmentDetails($shipment_id);
            
            if(!empty($fitments))
            {
                $this->ajaxDie(json_encode([ 'success' => 'Fetched Fitments', 'items' => $fitments['list'], 'tracking_url' => $fitments['tracking_url'], 'shipped_date' => $fitments['shipped_date'], ]));
            }
            
            $this->setErrorHeaders('SQL Query Failed');
        }
    }
    
    public function GetShipmentDetails($shipment_id = 0)
    {
        if(!is_null($shipment_id) && isset($shipment_id) && $shipment_id > 0)
        {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'sales_order_shipment_detail` WHERE shipment_id = '.$shipment_id;
            $result = Db::getInstance()->executeS($sql);

            $items_in_shipment = [];
            $shipmentData = Shoplync_partial_shipments::GetSingleShipment($shipment_id);
            $tracking_url = !empty($shipmentData) ? $shipmentData[0]['tracking_url'] : '';
            $shippedDate = !empty($shipmentData) ? $shipmentData[0]['shipped_date'] : '';
            
            if(!empty($result))
            {
                foreach($result as $item_detail)
                {
                    $sku = Trim($item_detail['sku']);
                    $searchResult = $this->searchProducts($sku, true, true);//search for sku only
                    if(is_array($searchResult) && isset($searchResult['found']) && $searchResult['found'])
                    {
                        $product = $searchResult['products'][0];
                        $product['toOrder'] = $item_detail['shipment_quantity'];
                        array_push($items_in_shipment, $product);
                    }
                }
                return ['tracking_url' => $tracking_url, 'shipped_date' => $shippedDate, 'list' => $items_in_shipment];
            }
        }
        return [];
    }
    /*
    *
    * Forked From https://github.com/PrestaShop/PrestaShop/blob/develop/controllers/admin/AdminCartRulesController.php
    *
    **/    
    public function searchProducts($search, $strict = false, $skuOnly = false)
    {
        $products;
        if($strict)
        {
            $products = $this->searchByNameStrict((int) $this->context->language->id, $search, null, null, $skuOnly);
        }
        else
        {
            $products = Product::searchByName((int) $this->context->language->id, $search);
        }
        
        if ($products) {
            foreach ($products as &$product) {
                $combinations = [];
                $productObj = new Product((int) $product['id_product'], false, (int) $this->context->language->id);
                $attributes = $productObj->getAttributesGroups((int) $this->context->language->id);
                $product['formatted_price'] = $product['price_tax_incl']
                    ? $this->context->getCurrentLocale()->formatPrice(Tools::convertPrice($product['price_tax_incl'], $this->context->currency), $this->context->currency->iso_code)
                    : '';
                    
                $product['link_rewrite'] = $productObj->link_rewrite == null ? '' : Context::getContext()->shop->getBaseURL(true).$productObj->link_rewrite;
                
                // Get cover image for your product
                $image = Image::getCover((int) $product['id_product']);
                $link = new Link();
                $imagePath = $productObj->link_rewrite == null ? null : $link->getImageLink($productObj->link_rewrite[Context::getContext()->language->id], $image['id_image'], 'small_default');
                
                $product['image_small_default'] = $imagePath == null ? '' : $imagePath;   
                
                foreach ($attributes as $attribute) {
                    if($attribute['reference'] == $search)
                    {
                        if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                            $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                        }
                        dbg::m('attribute: '.print_r($attribute, true));
                        $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . ' - ';
                        $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                        $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                        $combinations[$attribute['id_product_attribute']]['combination_reference'] = $attribute['reference'];
                        $combinations[$attribute['id_product_attribute']]['combination_mpn'] = $attribute['mpn'];
                        if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                            $price_tax_incl = Product::getPriceStatic((int) $product['id_product'], true, $attribute['id_product_attribute']);
                            $combinations[$attribute['id_product_attribute']]['formatted_price'] = $price_tax_incl
                                ? $this->context->getCurrentLocale()->formatPrice(Tools::convertPrice($price_tax_incl, $this->context->currency), $this->context->currency->iso_code)
                                : '';
                        }
                    }
                }
                if(!empty($combinations))
                {
                    foreach ($combinations as &$combination) {
                        $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                    }
                }

                $product['combinations'] = $combinations;
                $product['prestashopProduct'] = true;
                $product['toOrder'] = 1;
            }
        
            return [
                'products' => $products,
                'found' => true,
            ];
        } else {
            return ['found' => false, 'notfound' => $this->trans('No product has been found.', [], 'Admin.Catalog.Notification')];
        }
    }
    /**
     * Admin panel product search. (Renmamed searchByName -> searchByNameStrict)
     * instead of using the sql 'LIKE' qualifier it uses '=' to be more precise
     *
     * @param int $id_lang Language identifier
     * @param string $query Search query
     * @param Context|null $context Deprecated, obsolete parameter not used anymore
     * @param int|null $limit
     *
     * @return array|false Matching products
     *
     * Forked from https://github.com/PrestaShop/PrestaShop/blob/6f95f94dcc41858629c43f0f099f4beede68ac67/classes/Product.php#L4855
     *
     */
    protected function searchByNameStrict($id_lang, $query, Context $context = null, $limit = null, $skuOnly = false)
    {
        if ($context !== null) {
            Tools::displayParameterAsDeprecated('context');
        }
        $sql = new DbQuery();
        $sql->select('p.`id_product`, pl.`name`, p.`ean13`, p.`isbn`, p.`upc`, p.`mpn`, p.`active`, p.`reference`, m.`name` AS manufacturer_name, stock.`quantity`, product_shop.advanced_stock_management, p.`customizable`');
        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');
        
    
        if($skuOnly)
        {
            $where = 'p.`mpn` = \'' . pSQL($query) . '\'';
        }
        else 
        {
            $where = /*'pl.`name` = \'' . pSQL($query) . '\'
            OR p.`ean13` = \'' . pSQL($query) . '\'
            OR p.`isbn` = \'' . pSQL($query) . '\'
            OR p.`upc` = \'' . pSQL($query) . '\'
            OR p.`mpn` = \'' . pSQL($query) . '\'
            OR */
            'p.`reference` = \'' . pSQL($query) . '\'
            OR p.`supplier_reference` = \'' . pSQL($query) . '\'
            OR EXISTS(SELECT * FROM `' . _DB_PREFIX_ . 'product_supplier` sp WHERE sp.`id_product` = p.`id_product` AND `product_supplier_reference` = \'' . pSQL($query) . '\')';
        }


        $sql->orderBy('pl.`name` ASC');

        if ($limit) {
            $sql->limit($limit);
        }

        if (Combination::isFeatureActive()) {
            if($skuOnly)
            {
                $where .= ' OR EXISTS(SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute` `pa` WHERE pa.`id_product` = p.`id_product` AND pa.`mpn` = \'' . pSQL($query) . '\')';
            }
            else 
            {
                $where .= ' OR EXISTS(SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute` `pa` WHERE pa.`id_product` = p.`id_product` AND (pa.`reference` = \'' . pSQL($query) . '\'
                OR pa.`supplier_reference` = \'' . pSQL($query) . '\''
                /*OR pa.`ean13` = \'' . pSQL($query) . '\'
                OR pa.`isbn` = \'' . pSQL($query) . '\'
                OR pa.`mpn` = \'' . pSQL($query) . '\'
                OR pa.`upc` = \'' . pSQL($query) . '\*/.'))';
            }
        }
        
        $sql->where($where);
        $sql->join(Product::sqlStock('p', 0));

        $result = Db::getInstance()->executeS($sql);

        if (!$result) {
            return false;
        }

        $results_array = [];
        foreach ($result as $row) {
            $row['price_tax_incl'] = Product::getPriceStatic($row['id_product'], true, null, 2);
            $row['price_tax_excl'] = Product::getPriceStatic($row['id_product'], false, null, 2);
            $results_array[] = $row;
        }

        return $results_array;
    }
}