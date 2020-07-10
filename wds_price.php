<?php 
/**
* 2007-2019 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wds_price extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'wds_price';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'wdesigners';
        $this->need_instance = 1;


        parent::__construct();

        $this->displayName = $this->l('Price range');
        $this->description = $this->l('Displyays count of items in price range');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }


    public function install()
    {
        $sql_min = 'SELECT price FROM '._DB_PREFIX_.'product ORDER BY price ASC';
        $min_price = Db::getInstance()->getValue($sql_min);
        $sql_max = 'SELECT price FROM '._DB_PREFIX_.'product ORDER BY price DESC';
        $max_price = Db::getInstance()->getValue($sql_max);

        $sql_count = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'product WHERE price BETWEEN '.$min_price.' AND '.$max_price.'';
        $count = Db::getInstance()->getValue($sql_count);


        Configuration::updateValue('WDS_PRICE_MINPRICE', $min_price);
        Configuration::updateValue('WDS_PRICE_MAXPRICE', $max_price);
        Configuration::updateValue('WDS_PRICE_ITEMS', $count);

        return parent::install() &&
            $this->registerHook('displayFooter');
    }

    public function uninstall()
    {
        Configuration::deleteByName('WDS_PRICE_MINPRICE');
        Configuration::deleteByName('WDS_PRICE_MAXPRICE');
        Configuration::deleteByName('WDS_PRICE_ITEMS');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitWds_priceModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of module.
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
        $helper->submit_action = 'submitWds_priceModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues() /* Add values for inputs */
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Price range'),
                'icon' => 'dollar-sign',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'prefix' => '',
                        'name' => 'WDS_PRICE_MINPRICE',
                        'label' => $this->l('Minimum price'),
                    ),
                    array(
                        'type' => 'text',
                        'prefix' => '',
                        'name' => 'WDS_PRICE_MAXPRICE',
                        'label' => $this->l('Maximum price'),
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
            'WDS_PRICE_MINPRICE' => Configuration::get('WDS_PRICE_MINPRICE', null),
            'WDS_PRICE_MAXPRICE' => Configuration::get('WDS_PRICE_MAXPRICE', null),
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

        $min_price = Configuration::get('WDS_PRICE_MINPRICE');
        $max_price = Configuration::get('WDS_PRICE_MAXPRICE');
        
        $sql_count = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'product WHERE price BETWEEN '.$min_price.' AND '.$max_price.'';
        $count = Db::getInstance()->getValue($sql_count);

        Configuration::updateValue('WDS_PRICE_ITEMS', $count);
    }


    public function hookDisplayFooter()
    {
      $this->context->smarty->assign([
        'WDS_PRICE_ITEMS' => Configuration::get('WDS_PRICE_ITEMS'),
        'WDS_PRICE_MINPRICE' => Configuration::get('WDS_PRICE_MINPRICE'),
        'WDS_PRICE_MAXPRICE' => Configuration::get('WDS_PRICE_MAXPRICE')
      ]);
      return $this->display(__FILE__, 'wds_price.tpl');
    }
}