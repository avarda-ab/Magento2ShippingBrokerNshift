<?php

/**
 * @author Avarda Team
 * @copyright Copyright © Avarda. All rights reserved.
 */

declare(strict_types=1);

namespace Avarda\ShippingBrokerNshift\Model\Provider;

use Avarda\ShippingBroker\Api\Gateway\Request\CustomAttributeBuilderInterface;
use Avarda\ShippingBroker\Api\Gateway\Response\ParserInterface;
use Avarda\ShippingBroker\Api\ProviderInterface;

/**
 * nShift / Unifaun provider implementation.
 */
class Nshift implements ProviderInterface
{
    public const CODE = 'nshift';

    protected ParserInterface $responseParser;
    protected array $customAttributesPool;

    /**
     * @param CustomAttributeBuilderInterface[] $customAttributesPool
     */
    public function __construct(
        ParserInterface $responseParser,
        array $customAttributesPool = []
    ) {
        $this->responseParser = $responseParser;
        $this->customAttributesPool = $customAttributesPool;
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getResponseParser(): ParserInterface
    {
        return $this->responseParser;
    }

    public function getCustomAttributesPool(): array
    {
        return $this->customAttributesPool;
    }

    public function shouldInjectFallbackLine(): bool
    {
        return true;
    }

    public function shouldLoadCheckoutScript(): bool
    {
        return true;
    }
}
