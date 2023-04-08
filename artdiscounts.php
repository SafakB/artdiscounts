<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ArtDiscounts extends Module
{
    public function __construct()
    {
        $this->name = 'artdiscounts';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Artonomi';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->displayName = 'Art Discounts';
        $this->description = 'Apply an instant discount on the cheapest product when there are 3 or more products in the cart.';
        parent::__construct();
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('actionCartUpdateQuantityBefore') &&
            $this->registerHook('actionCartUpdateQuantityAfter') &&
            $this->registerHook('displayShoppingCartFooter');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookHeader()
    {
        $this->context->controller->registerStylesheet(
            'artdiscounts-css',
            'modules/' . $this->name . '/artdiscounts.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'artdiscounts',
            'modules/' . $this->name . '/artdiscounts.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function hookDisplayShoppingCartFooter($params)
    {
        return $this->hookActionCartSave($params);
    }

    public function hookActionCartUpdateQuantityBefore($params)
    {
        $this->hookActionCartSave($params);
    }

    public function hookActionCartUpdateQuantityAfter($params)
    {
        $this->hookActionCartSave($params);
    }

    public function hookActionCartSave($params)
    {
        $cart = $this->context->cart;
        $products = $cart->getProducts();
        $cheapestProductPrice = null;

        $productCount = 0;
        foreach ($products as $product) {
            $productCount += $product['quantity'];
        }

        if ($productCount >= 3) {
            foreach ($products as $product) {
                if ($cheapestProductPrice === null || $product['price'] < $cheapestProductPrice) {
                    $cheapestProductPrice = $product['price'];
                }
            }

            $discountId = (int) Configuration::get('ART_DISCOUNTS_DISCOUNT_ID');
            $cartRule = new CartRule($discountId);

            if (!Validate::isLoadedObject($cartRule)) {
                $cartRule = new CartRule();
                $cartRule->name = array_fill_keys(Language::getIDs(), '3 Al 2 Öde');
                $cartRule->id_customer = $cart->id_customer;
                $cartRule->date_from = date('Y-m-d H:i:s');
                $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+1 day'));
                $cartRule->quantity = 1;
                $cartRule->quantity_per_user = 1;
                $cartRule->free_shipping = false;
                $cartRule->reduction_percent = 0;
                $cartRule->active = 1;
            }

            $cartRule->reduction_amount = $cheapestProductPrice;
            $cartRule->save();


            $cart->removeCartRule((int) Configuration::get('ART_DISCOUNTS_DISCOUNT_ID'));
            $cart->addCartRule($cartRule->id);
            Configuration::updateValue('ART_DISCOUNTS_DISCOUNT_ID', $cartRule->id);
        } else {
            $cart->removeCartRule((int) Configuration::get('ART_DISCOUNTS_DISCOUNT_ID'));
        }
        // Assign the variables to the Smarty template
        $this->context->smarty->assign([
            'cart' => $cart,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/displayCartTotalPriceBlock.tpl');
    }

    public function hookDisplayCartTotalPriceBlock($params)
    {
        return $this->display(__FILE__, 'views/templates/hook/displayCartTotalPriceBlock.tpl');
    }
}
