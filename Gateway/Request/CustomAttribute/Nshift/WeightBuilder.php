<?php

/**
 * @author Avarda Team
 * @copyright Copyright © Avarda. All rights reserved.
 */

declare(strict_types=1);

namespace Avarda\ShippingBrokerNshift\Gateway\Request\CustomAttribute\Nshift;

use Avarda\ShippingBroker\Api\Gateway\Request\CustomAttributeBuilderInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;

class WeightBuilder implements CustomAttributeBuilderInterface
{
    public const ATTRIBUTE = 'weight';

    protected StoreConfigManagerInterface $storeConfigManager;
    protected ?StoreConfigInterface $storeConfig = null;

    public function __construct(
        StoreConfigManagerInterface $storeConfigManager
    ) {
        $this->storeConfigManager = $storeConfigManager;
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
        $weight = 0;
        foreach ($this->getItems($cart) as $item) {
            $weight += $this->getWeightInGrams((float) $item->getWeight() * $item->getQty());
        }

        return (string) $weight;
    }

    /**
     * The quote repository populates the items data key only for active quotes, and a renewed Avarda
     * purchase is built from an inactive one.
     *
     * @return CartItemInterface[]
     */
    private function getItems(CartInterface $cart): array
    {
        $items = $cart->getItems();
        if ($items === null && $cart instanceof Quote) {
            $items = $cart->getAllVisibleItems();
        }

        return $items ?? [];
    }

    public function getWeightInGrams(float $weight): int
    {
        if ($this->getStoreConfig()->getWeightUnit() == 'kgs') {
            return (int) ($weight * 1000);
        } else {
            return (int) ($weight * 453.592);
        }
    }

    private function getStoreConfig(): StoreConfigInterface
    {
        if (!$this->storeConfig) {
            $storeConfigs = $this->storeConfigManager->getStoreConfigs();
            $this->storeConfig = current($storeConfigs);
        }

        return $this->storeConfig;
    }
}
