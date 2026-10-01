<?php

/**
 * @author Avarda Team
 * @copyright Copyright © Avarda. All rights reserved.
 */

declare(strict_types=1);

namespace Avarda\ShippingBrokerNshift\Gateway\Response;

use Avarda\ShippingBroker\Api\Gateway\Response\ParserInterface;

/**
 * Parse Gateway response regarding shipping rates
 *
 * Supports both the legacy Unifaun widget data and the nShift Delivery Checkout widget data.
 */
class GetShippingRateStatusParser implements ParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(array $response): array | bool
    {
        if (isset($response['Modules']) && count($response['Modules']) > 0) {
            if (!isset($response['Modules'][0]['ShippingModule'])) {
                return false;
            }
            foreach ($response['Modules'] as $module) {
                $selectedShippingOption = $module['SelectedShippingOption'] ?? null;
                if (!$selectedShippingOption) {
                    return $this->getLowestValueShippingData($module);
                } else {
                    return $this->getSelectedShippingData($selectedShippingOption, $module);
                }
            }
        }

        return false;
    }

    private function getSelectedShippingData(array $selectedShippingOption, array $module): array | bool
    {
        $optionId = $selectedShippingOption['OptionId'] ?? $selectedShippingOption['OptionIds'][0] ?? null;
        if ($optionId === null) {
            return false;
        }

        $widgetOption = [];
        foreach ($module['WidgetDataJson']['options'] ?? [] as $option) {
            if ($optionId == $this->getOptionId($option)) {
                $widgetOption = $option;
                break;
            }
        }
        if (!$widgetOption) {
            return false;
        }

        $selectedAgentId = $selectedShippingOption['PickupPointId']
            ?? $selectedShippingOption['SelectedAgentId']
            ?? null;
        $selectedAgentId = $selectedAgentId === null ? null : (string) $selectedAgentId;
        $selectedOptionName = $selectedShippingOption['OptionName']
            ?? $selectedShippingOption['SelectedOptionName']
            ?? null;
        $price = $selectedShippingOption['Price'] ?? null;

        return $this->buildShippingData(
            $widgetOption,
            $selectedOptionName === null ? null : (string) $selectedOptionName,
            $selectedAgentId,
            $price ?? $this->getOptionPrice($widgetOption),
            $this->getWidgetAgent($widgetOption, $selectedAgentId),
        );
    }

    private function getLowestValueShippingData(array $module): array | bool
    {
        $widgetOption = [];
        $minPrice = null;
        foreach ($module['WidgetDataJson']['options'] ?? [] as $option) {
            $optionPrice = $this->getOptionPrice($option);
            // Options without a price have no matching price rule; picking one would mean free shipping
            if ($optionPrice === null || ($option['valid'] ?? true) === false) {
                continue;
            }
            if (null === $minPrice || $minPrice > $optionPrice) {
                $widgetOption = $option;
                $minPrice = $optionPrice;
            }
        }
        if (!$widgetOption) {
            return false;
        }

        return $this->buildShippingData($widgetOption, null, null, $minPrice, null);
    }

    private function buildShippingData(
        array $widgetOption,
        ?string $selectedOptionName,
        ?string $selectedAgentId,
        int | float | string | null $price,
        ?array $widgetAgent,
    ): array {
        return [
            'selectedOptionName' => $selectedOptionName,
            'selectedAgentId' => $selectedAgentId,
            'price' => $price,
            'optionId' => $this->getOptionId($widgetOption),
            'carrierId' => $widgetOption['carrierId'] ?? null,
            'priceValue' => $this->getOptionPrice($widgetOption) ?? $price,
            'defaultPrice' => $widgetOption['defaultPrice'] ?? $widgetOption['originalPrice'] ?? null,
            'taxRate' => $widgetOption['taxRate'] ?? null,
            'serviceId' => $widgetOption['serviceId'] ?? $widgetOption['carrierProductId'] ?? null,
            'widgetAgent' => $widgetAgent,
        ];
    }

    private function getOptionId(array $option): ?string
    {
        $optionId = $option['id'] ?? $option['optionId'] ?? null;

        return $optionId === null ? null : (string) $optionId;
    }

    private function getOptionPrice(array $option): int | float | null
    {
        $price = $option['priceValue'] ?? $option['price'] ?? null;

        return is_numeric($price) ? $price + 0 : null;
    }

    private function getWidgetAgent(array $widgetOption, ?string $selectedAgentId): array
    {
        if ($selectedAgentId === null) {
            return [];
        }

        foreach ($widgetOption['agents'] ?? [] as $agent) {
            if (isset($agent['id']) && $selectedAgentId == $agent['id']) {
                return $agent;
            }
        }

        // The nShift widget names pickup point fields differently; map them to the keys ShippingDetails reads
        foreach ($widgetOption['pickupPoints'] ?? [] as $pickupPoint) {
            if (isset($pickupPoint['pickupPointId']) && $selectedAgentId == $pickupPoint['pickupPointId']) {
                return [
                    'id' => $pickupPoint['pickupPointId'],
                    'name' => $pickupPoint['name'] ?? null,
                    'address1' => $pickupPoint['address1'] ?? null,
                    'address2' => $pickupPoint['address2'] ?? null,
                    'zipCode' => $pickupPoint['postalCode'] ?? null,
                    'city' => $pickupPoint['city'] ?? null,
                    'countryCode' => $pickupPoint['countryCode'] ?? null,
                    'mapLatitude' => $pickupPoint['latitude'] ?? null,
                    'mapLongitude' => $pickupPoint['longitude'] ?? null,
                ];
            }
        }

        return [];
    }
}
