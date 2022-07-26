<?php
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
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

//Include the class of the new model 
include_once dirname (__FILE__).'/classes/sales_order_shipment.php';
include_once dirname (__FILE__).'/classes/sales_order_shipment_detail.php';

class Shoplync_partial_shipments extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'shoplync_partial_shipments';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Shoplync';
        $this->need_instance = 0;


        $this->controllers = array('query');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Partial Shipment Notifier');
        $this->description = $this->l('Will notify the user about their order being sent out as a partial shipment and will display what exact items where shipped in each box.');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPLYNC_PARTIAL_SHIPMENTS_LIVE_MODE', true);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('addWebserviceResources') &&
            $this->registerHook('actionOrderStatusUpdate');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPLYNC_PARTIAL_SHIPMENTS_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitShoplync_partial_shipmentsModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_partial_shipmentsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected static function GetOrderStatuses()
    {
        $sql = 'SELECT os.id_order_state, osl.name FROM `' . _DB_PREFIX_ . 'order_state` AS os '
        .'LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` AS osl ON os.id_order_state = osl.id_order_state '
        .'WHERE osl.id_lang = 1';
        
        $result = Db::getInstance()->executeS($sql);
        
        if(empty($result))
            return [];
        
        return $result;
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'SHOPLYNC_PARTIAL_SHIPMENTS_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SHOPLYNC_PARTIAL_SHIPMENTS_LIVE_MODE' => Configuration::get('SHOPLYNC_PARTIAL_SHIPMENTS_LIVE_MODE', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
        
        Media::addJsDef([
            'shipmentajax_link' => $this->context->link->getModuleLink('shoplync_partial_shipments', 'query', array(), true),
        ]);
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
    }
    public static function GetShipments($order_id)
    {
        if(!is_null($order_id) && isset($order_id))
        {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'sales_order_shipment` WHERE ps_order_id = '.$order_id;
            $result = Db::getInstance()->executeS($sql);
        
            if(empty($result))
                return [];
            
            return $result;
        }
    }
    public static function GetSingleShipment($shipment_id)
    {
        if(!is_null($shipment_id) && isset($shipment_id))
        {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'sales_order_shipment` WHERE shipment_id = '.$shipment_id;
            $result = Db::getInstance()->executeS($sql);
        
            if(empty($result))
                return [];
            
            return $result;
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        $shipmentElement = '<!-- No Shipments to list -->';
        //error_log("params od: ".$params['order']->id);
        
        $shipments = self::GetShipments($params['order']->id);
        if(!empty($shipments) && Configuration::get('SHOPLYNC_PARTIAL_SHIPMENTS_LIVE_MODE', true))
        {
            $options = [];
            foreach($shipments as $shipment)
            {
                array_push($options, '<option value="'.$shipment['shipment_id'].'">'.$shipment['tracking_number'].'</option>');
            }
            
            $shipmentElement = '<div class="box text-center">'
                .'<h3>Items In Shipment</h3>'
                .'<select name="tracking_numbers" class="form-control form-control-select" onchange="getShipments(this)">'
                .'<option value>-- Please Select A Shipment --</option>'
                .implode('', $options)
                .'</select>'
                .'<div id="shipmentDetailsTable"></div>'
                .'</div>';
        }
        return $shipmentElement;
    }
    
    public function hookActionOrderStatusUpdate()
    {
        /* Place your code here. */
    }
    
    public function hookAddWebserviceResources()
    {
        return array(
            'sales_order_shipments' => array('description' => 'Manage Sales Order Shipments', 'class' => 'sales_order_shipment'),
            'sales_order_shipment_details' => array('description' => 'Manage Sales Order Shipment Details', 'class' => 'sales_order_shipment_detail'),
        );
     
    }
}
