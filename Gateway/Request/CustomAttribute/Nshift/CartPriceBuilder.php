<?php

/**
 * @author Avarda Team
 * @copyright Copyright © Avarda. All rights reserved.
 */

declare(strict_types=1);

namespace Avarda\ShippingBrokerNshift\Gateway\Request\CustomAttribute\Nshift;

use Avarda\ShippingBroker\Api\Gateway\Request\CustomAttributeBuilderInterface;
use Magento\Quote\Api\Data\CartInterface;

class CartPriceBuilder implements CustomAttributeBuilderInterface
{
    public const ATTRIBUTE = 'cartprice';

    /**
     * @inheritdoc
     */
    public function build(CartInterface $cart): string
    {
        return self::ATTRIBUTE . '=' . $this->getValue($cart);
    }

    /**
     * Product total including tax and discounts; shipping is left out so price-based
     * rules do not depend on the shipping option being chosen.
     */
    private function getValue(CartInterface $cart): string
    {
        $cart->collectTotals();
        $address = $cart->getShippingAddress();
        $price = (float) $address->getSubtotalInclTax()
            + (float) $address->getDiscountAmount()
            + (float) $address->getShippingDiscountAmount();

        return number_format(max($price, 0), 2, '.', '');
    }
}
