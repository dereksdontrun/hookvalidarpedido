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

class Hookvalidarpedido extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'hookvalidarpedido';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Sergio';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Procesos al validar pedido');
        $this->description = $this->l('El módulo contiene el hook hookActionValidateOrder. Se utiliza para procesos varios a realizar cuando entra un pedido a Prestashop. P.ej. meter al cliente a grupo Unicornio si procede.');

        $this->confirmUninstall = $this->l('¿Me vas a desinstalar?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('HOOKVALIDARPEDIDO_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Configuration::deleteByName('HOOKVALIDARPEDIDO_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitHookvalidarpedidoModule')) == true) {
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
        $helper->submit_action = 'submitHookvalidarpedidoModule';
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
                        'name' => 'HOOKVALIDARPEDIDO_LIVE_MODE',
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
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'HOOKVALIDARPEDIDO_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'HOOKVALIDARPEDIDO_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
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
            'HOOKVALIDARPEDIDO_LIVE_MODE' => Configuration::get('HOOKVALIDARPEDIDO_LIVE_MODE', true),
            'HOOKVALIDARPEDIDO_ACCOUNT_EMAIL' => Configuration::get('HOOKVALIDARPEDIDO_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'HOOKVALIDARPEDIDO_ACCOUNT_PASSWORD' => Configuration::get('HOOKVALIDARPEDIDO_ACCOUNT_PASSWORD', null),
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
        if (Tools::getValue('module_name') == $this->name) {
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
    }

    public function hookActionValidateOrder($params)
    {
        //Queremos comprobar si el pedido entrante es válido (supuestamente si) y el número de pedidos que lleva el cliente en el año corriente. Si hay al menos uno se comprueba si pertenece al grupo Unicornio 2022 (o año en que estemos) y si no es así se le mete, de modo que para el tercer pedido tendrá asignado el cupón de envío gratis.
        //09/08/2022 Además de asignarle el grupo, se le pone como grupo por defecto.        
        if ($params) {
            //sacamos el estado de entrada del pedido, queremos evitar Pedidos virtuales, tpv, amazon, worten, webservice, etc
            $orderStatus = $params['orderStatus'];
            if (!Validate::isLoadedObject($orderStatus))
            {                
                return;                
            }

            $id_orderStatus = (int)$orderStatus->id;

            //solo aceptamos pedidos en Pago aceptado, Pago por transferencia pendiente y Canarias.
            //24/11/2022 Añadimos los estados de entrada de pedido válidos Sequra en revisión y Esperando pago con Paypal
            // 14/12/2022 Añado Esperando el pago con tarjeta de crédito y Esperando el pago con un método de pago local,
            if ( ($id_orderStatus) && (($id_orderStatus == Configuration::get(PS_OS_PAYMENT)) || ($id_orderStatus == Configuration::get(PS_OS_BANKWIRE)) || ($id_orderStatus == Configuration::get(CLICKCANARIAS_STATE)) || ($id_orderStatus == Configuration::get(SEQURA_OS_NEEDS_REVIEW)) || ($id_orderStatus == Configuration::get(PS_CHECKOUT_STATE_WAITING_PAYPAL_PAYMENT)) || ($id_orderStatus == Configuration::get(PS_CHECKOUT_STATE_WAITING_CREDIT_CARD_PAYMENT)) || ($id_orderStatus == Configuration::get(PS_CHECKOUT_STATE_WAITING_LOCAL_PAYMENT))) ) {

                //sacamos la info del pedido, para saber si viene de amazon, etc
                $order = $params['order'];
                //validate that the Order is an Object and has a valid ID
                if (Validate::isLoadedObject($order))
                {            
                    //comprobamos que no sea de amazon, los pedidos amazon entran con module = amazon y payment = Amazon MarketPlace. Usamos stripos() que busca una cadena sin tener en cuenta mayúsculas o minúsculas. Tampoco contamos los de worten, que van por módulo mirakl
                    //02/01/2025 quitamos también tiktok y webservice
                    $paymentMethod = $order->payment;
                    $module = $order->module;
                    
                    if ((stripos($paymentMethod, 'amazon') === false ) && (stripos($module, 'amazon') === false ) && (stripos($paymentMethod, 'worten') === false ) && (stripos($module, 'mirakl') === false ) && (stripos($module, 'webservice') === false ) && (stripos($module, 'tiktok') === false )) {

                        //el pedido es correcto, comprobamos el cliente
                        $customer = $params['customer'];
                        if (Validate::isLoadedObject($customer))
                        {            
                            $id_customer = (int)$customer->id;

                            //16/11/2022 comprobamos aquí también que el email de cliente no sea de amazon o worten
                            $customer_email = $customer->email;
                            if ((stripos($customer_email, 'marketplace.amazon') != false ) || (stripos($customer_email, 'mirakl') != false )) {
                                return;
                            }

                            //obtenemos el número de pedido realizados por el cliente en el transcurso del año, con una consulta que obtiene el year actual al vuelo
                            $sql_pedidos_ytd = 'SELECT COUNT(id_order) FROM lafrips_orders 
                            WHERE valid = 1 AND current_state != 5
                            AND date_add >= DATE_FORMAT(NOW() ,"%Y-01-01 00:00:00") 
                            AND id_customer = '.$id_customer;

                            $pedidos_ytd = Db::getInstance()->getValue($sql_pedidos_ytd);

                            //el pedido actual no está incluido en la consulta de arriba ya que no está creado del todo, de modo que si la consulta da resultado 1 o más, será válido. Queremos mínimo 2 contando el actual

                            if (is_null($pedidos_ytd) || $pedidos_ytd < 1) {
                                return;
                            } 

                            //tiene al menos un pedido, comprobamos si el cliente pertenece al grupo "Unicornios YEAR" siendo year el año corriente. Para ello obtenemos el año actual y formamos la cadena para buscar el nombre del grupo y obtener el id del grupo. Los grupos se tendrán que ir actualizando cada año o lo crearé aquí si no lo encuentra
                            $nombre_grupo = 'Unicornios '.date("Y");

                            $group = Group::searchByName($nombre_grupo);

                            if (!$id_group = $group['id_group']) {
                                //no ha encontrado el grupo
                                return;
                            }

                            //obtenemos los grupos a que pertenece el cliente. Ya está instanciado:
                            $customer_groups = $customer->getGroups();
                            if (!in_array($id_group, $customer_groups)) {
                                //no pertenece al grupo, se lo asignamos. La función requiere un array con los ids de grupo
                                $customer->addGroups(array($id_group));
                                //09/08/2022 Además de asignarle el grupo, se le pone como grupo por defecto.
                                //12/08/2022 Ponemos newsletter a 1 
                                $customer->id_default_group = $id_group;
                                $customer->newsletter = 1;
	                            $customer->save();
                            } else {
                                return;
                            }
                                        
                        } //if validate customer

                    } //if módulo pago

                } //if validate order

            }//if estados

        } //if $params

    } // function
}
