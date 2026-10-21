<?php

/**
 * @author Avarda Team
 * @copyright Copyright © Avarda. All rights reserved.
 */

declare(strict_types=1);

namespace Avarda\ShippingBrokerNshift\Gateway\Request\CustomAttribute\Nshift;

use Avarda\ShippingBroker\Api\Gateway\Request\CustomAttributeBuilderInterface;
use Magento\Quote\Api\Data\CartInterface;

class WeightKgBuilder implements CustomAttributeBuilderInterface
{
    public const ATTRIBUTE = 'weight_kg';

    protected WeightBuilder $weightBuilder;

    public function __construct(
        WeightBuilder $weightBuilder,
    ) {
        $this->weightBuilder = $weightBuilder;
    }

    /**
     * @inheritdoc
     */
    public function build(CartInterface $cart): string
    {
        return self::ATTRIBUTE . '=' . $this->getValue($cart);
    }

    private function getValue(CartInterface $cart): string
    {
        return (string) round($this->weightBuilder->getTotalWeightInGrams($cart) / 1000, 3);
    }
}
