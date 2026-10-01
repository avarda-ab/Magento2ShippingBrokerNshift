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
                if (!$selectedShippingOption || !is_array($selectedShippingOption)) {
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
        $optionId = $selectedShippingOption['OptionIds'][0] ?? null;
        if ($optionId === null) {
            return false;
        }
        $selectedOptionName = $selectedShippingOption['SelectedOptionName'] ?? null;
        $selectedAgentId = $selectedShippingOption['SelectedAgentId'] ?? null;
        $price = $selectedShippingOption['Price'] ?? null;

        $widgetOption = [];
        foreach ($module['WidgetDataJson']['options'] ?? [] as $option) {
            if ($optionId == ($option['id'] ?? null)) {
                $widgetOption = $option;
                break;
            }
        }
        if (!$widgetOption) {
            return false;
        }

        $widgetAgent = [];
        foreach ($widgetOption['agents'] ?? [] as $agent) {
            if (isset($agent['id']) && $selectedAgentId == $agent['id']) {
                $widgetAgent = $agent;
                break;
            }
        }

        return [
            'selectedOptionName' => $selectedOptionName,
            'selectedAgentId' => $selectedAgentId,
            'price' => $price,
            'optionId' => $optionId,
            'carrierId' => $widgetOption['carrierId'] ?? null,
            'priceValue' => $widgetOption['priceValue'] ?? $price,
            'defaultPrice' => $widgetOption['defaultPrice'] ?? null,
            'taxRate' => $widgetOption['taxRate'] ?? null,
            'serviceId' => $widgetOption['serviceId'] ?? null,
            'widgetAgent' => $widgetAgent,
        ];
    }

    private function getLowestValueShippingData(array $module): array | bool
    {
        $widgetOption = [];
        $minPrice = null;
        foreach ($module['WidgetDataJson']['options'] ?? [] as $option) {
            // nShift omits priceValue for options that no price rule matches; such an option is not free.
            if (!isset($option['priceValue']) || !is_numeric($option['priceValue'])) {
                continue;
            }
            if (null === $minPrice || $minPrice > (float) $option['priceValue']) {
                $widgetOption = $option;
                $minPrice = (float) $option['priceValue'];
            }
        }
        if (!$widgetOption) {
            return false;
        }
        $priceValue = $widgetOption['priceValue'];

        return [
            'selectedOptionName' => null,
            'selectedAgentId' => null,
            'price' => $priceValue,
            'optionId' => $widgetOption['id'] ?? null,
            'carrierId' => $widgetOption['carrierId'] ?? null,
            'priceValue' => $priceValue,
            'defaultPrice' => $widgetOption['defaultPrice'] ?? null,
            'taxRate' => $widgetOption['taxRate'] ?? null,
            'serviceId' => $widgetOption['serviceId'] ?? null,
            'widgetAgent' => null,
        ];
    }
}
