<?php

/**
 * @author Avarda Team
 * @copyright Copyright © Avarda. All rights reserved.
 */

declare(strict_types=1);

namespace Avarda\ShippingBrokerNshift\ViewModel;

use Avarda\ShippingBroker\Model\Provider\Pool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class UnifaunScript implements ArgumentInterface
{
    protected Pool $providerPool;
    protected LoggerInterface $logger;

    public function __construct(
        Pool $providerPool,
        LoggerInterface $logger
    ) {
        $this->providerPool = $providerPool;
        $this->logger = $logger;
    }

    public function isActive(): bool
    {
        try {
            return $this->providerPool->getActive()->shouldLoadCheckoutScript();
        } catch (LocalizedException $e) {
            $this->logger->warning(
                'Avarda ShippingBroker: cannot resolve active provider, skipping Unifaun script.',
                ['exception' => $e]
            );
            return false;
        }
    }
}
