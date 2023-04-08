<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ArtDiscountsApplyDiscountModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

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

            $discountId = (int) Configuration::get('ART_DISCOUNTS_DISCOUNT_ID_' . $cart->id);
            $cartRule = new CartRule($discountId);

            if (!Validate::isLoadedObject($cartRule)) {
                $cartRule = new CartRule();
                $cartRule->name = array_fill_keys(Language::getIDs(), '3 Al 2 Öde');
                //$cartRule->id_customer = $cart->id_customer;
                $cartRule->date_from = date('Y-m-d H:i:s');
                $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+1 week'));
                $cartRule->quantity = 9999;
                $cartRule->quantity_per_user = 9999;
                $cartRule->free_shipping = false;
                $cartRule->reduction_percent = 0;
                $cartRule->active = 1;
            }

            $cartRule->reduction_amount = $cheapestProductPrice;
            $cartRule->save();


            $cart->removeCartRule((int) Configuration::get('ART_DISCOUNTS_DISCOUNT_ID_' . $cart->id));
            $cart->addCartRule($cartRule->id);
            Configuration::updateValue('ART_DISCOUNTS_DISCOUNT_ID_' . $cart->id, $cartRule->id);
        } else {
            $cart->removeCartRule((int) Configuration::get('ART_DISCOUNTS_DISCOUNT_ID_' . $cart->id));
        }

        // Return a JSON response
        $this->ajaxDie(json_encode([
            'success' => true,
        ]));
    }
}
