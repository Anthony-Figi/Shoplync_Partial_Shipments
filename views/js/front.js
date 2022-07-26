/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
function getShipments(selectObject)
{
    var table = document.getElementById('shipmentDetailsTable');
    if(selectObject.value)
    {
        var ShipmentValue = selectObject.value;
        //send request to server and get data to display via ajax
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: shipmentajax_link, 
            data: {
                ajax: true,
                action: 'getShipmentDetails',//lowercase with action name
                shipment_id: ShipmentValue
            },
            success : function (data) {
                if(data && table)
                {
                    console.log('SUCCESS');
                    console.log(data);
                    table.innerHTML = '';
                    GenerateList('#shipmentDetailsTable', data.items);
                    
                    if(data.tracking_url != null && data.tracking_url.length > 0)
                        table.innerHTML += '<a href="'+data.tracking_url+'" target="_blank" class="button btn btn-primary">View Tracking</a>';
                    
                    if(data.shipped_date != null && data.shipped_date.length > 0)
                        $("#table_itemlist thead").prepend('<tr><th class="col-lg-9">Shipped On: '+data.shipped_date+'</th><tr>');
                }
            },
            error : function (data){
                console.log('FAILED');
                console.log(data);
            }
        }); 
    }
    else
        table.innerHTML = '';
}

var baseTable = '<table class="table table-hover" id="table_itemlist">'
    + '<thead><tr>'
    + '<th class="hidden-md-down col-lg-1 checkbox-col nopadding-right" id="orderlist-product-checkbox">&nbsp;</th>'
    + '<th class="hidden-md-down col-lg-2 orderlist-product-partNo">Part Number</th>'
    + '<th class="col-xs-7 col-lg-3 orderlist-product-desc">Items</th>'
    + '<th class="hidden-md-down col-lg-2 orderlist-product-brand"  id="orderlist-product-brand">Brand</th>'
    + '<th class="hidden-md-down col-lg-1 orderlist-product-quantity" id="orderlist-product-qty">Quantity</th>'
    + '</tr></thead><tbody id="table_body"></tbody></table>';

var warningNotice = '<div class="alert alert-warning" id="'+noProductsInListName.replace('#', '')+'"></div>';
/**
* Generates a table for 'found' part numbers
*/
function GenerateList(objectName = '#shipmentDetailsTable', dataList){
    if(Array.isArray(dataList))
    {
        $(objectName).append(baseTable);
        
        for(idx in dataList)
        {
            $('#table_itemlist #table_body').append(CreateProductObject(dataList[idx]));
        }
    }
    else 
    {
        
        $(objectName).append(warningNotice);
        $(noProductsInListName).append('Failed to load product or received no response from the server. Please try again later.');
    }
}
/**
* Gets the products combination name and combination part number
*/
function GetSkuNumber(product)
{
    return GetReferenceNumber(product, true)[1];
}
function GetReferenceNumber(product, getSku = false){
    if(product != null && product.hasOwnProperty('combinations') && !Array.isArray(product.combinations))
    {
        var combo = product.combinations[Object.keys(product.combinations)[0]];
        var nameTxt = combo.attributes;
        var partNoTxt = getSku ? combo.combination_mpn : combo.combination_reference;
        
        return [nameTxt, partNoTxt];
    }
    return ['',''];
}
/**
* Generates a table row for a 'found' part number
*/
function CreateProductObject(product){
    if(product !== null){
        var name = product.hasOwnProperty('name') && product.name != null ? product.name : "";
        var mfgName = product.hasOwnProperty('manufacturer_name') && product.manufacturer_name != null ? product.manufacturer_name : "";
        var partNo = product.hasOwnProperty('reference') && product.reference != null ? product.reference : "";
        var mpn = product.hasOwnProperty('mpn') && product.mpn != null ? product.mpn : "";
        var productLink = product.hasOwnProperty('link_rewrite') && product.link_rewrite != null && product.link_rewrite.length > 0 ? product.link_rewrite : "#";
        var productImage = product.hasOwnProperty('image_small_default') && product.image_small_default != null && product.image_small_default.length > 0 ? product.image_small_default : "";
        var qty = product.hasOwnProperty('toOrder') && product.toOrder != null && product.toOrder > 0 ? product.toOrder : 1;
        
        if(!(/^(https?:\/\/)/.test(productImage))){ productImage = 'https://'+productImage; }
        
        if(product.hasOwnProperty('combinations') && !Array.isArray(product.combinations))
        {
            var combo = GetReferenceNumber(product);
            name += ' '+combo[0];
            partNo = partNo.length > 0 ? partNo : combo[1];
            mpn = mpn.length > 0 ? mpn : GetSkuNumber(product);
        }
        
        var tableRow = '<tr><td class="col-xs-12 hidden-md col-lg-1 checkbox-col nopadding-right"><img src="'+productImage+'" alt="'+name.trim()+'"></td>'
            + '<td class="col-xs-12 col-md-3 col-lg-2 checkbox-col orderlist-product-partNo"><p>MPN: <span class="product_partNo">'+partNo+'</span></p><p>SKU: <span class="product_sku">'+mpn+'</spam></p></td>'
            + '<td class="col-xs-12 col-lg-3 checkbox-col orderlist-product-desc"><p class="product_name"><a href="'+productLink+'" target="_blank">'+name.trim()+'</a></p></td>'
            + '<td class="hidden-md-down col-lg-2 checkbox-col orderlist-product-brand"><p class="product_brand">'+mfgName+'</p></td>'
            + '<td class="col-xs-4 col-sm-3 col-lg-1 checkbox-col orderlist-product-quantity"><p>'+qty+'</p></td>'
            + '</tr>';

        return tableRow;
    }
}