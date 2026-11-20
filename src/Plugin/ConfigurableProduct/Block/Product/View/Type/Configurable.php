<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPriceCalculation\Helper\Config;
use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Adds the calculated prices of the products of a configurable product to its configuration ("calculatedOptionPrices").
 */
class Configurable
{
    /**
     * @param Json $json
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Data $helper
     * @param Config $configHelper
     * @param LoggerInterface $logging
     */
    public function __construct(
        protected Json $json,
        protected Arrays $arrays,
        protected Variables $variables,
        protected Data $helper,
        protected Config $configHelper,
        protected LoggerInterface $logging
    ) {
    }

    /**
     * Adds the calculated prices of the configurable product and of its products.
     *
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        string $result
    ): string {
        $config = $this->json->decode($result);

        if (! is_array($config)) {
            return $result;
        }

        /** @var array<string, mixed> $config */
        $config = $this->configHelper->processConfig($config, $subject->getProduct());

        $calculatedOptionPrices = [];

        foreach ($subject->getAllowProducts() as $usedProduct) {
            $usedProductId = $this->variables->intValue($usedProduct->getId());

            foreach ($this->helper->getCalculations() as $calculation) {
                if (! $calculation->hasProductCalculation($usedProduct)) {
                    continue;
                }

                try {
                    $prices = $this->helper->getProductCalculation($calculation, $usedProduct);
                    $tierPrices = $this->helper->getProductTierCalculation($calculation, $usedProduct);
                } catch (LocalizedException $exception) {
                    $this->logging->error($exception);

                    continue;
                }

                $productPriceValue = $prices[ 'finalPrice' ][ 'amount' ];

                $prices[ 'tierPrices' ] = array_map(
                    fn(array $tierPrice): array => $tierPrice + [
                        'percentage' => $this->configHelper->getFormattedSavePercent(
                            $this->configHelper->calculateSavePercent($productPriceValue, $tierPrice[ 'price' ])
                        )
                    ],
                    $tierPrices
                );

                $calculatedOptionPrices[ $calculation->getCode() ][ $usedProductId ] = $prices;
            }
        }

        $config[ 'calculatedOptionPrices' ] =
            ['default' => $this->arrays->getValue($config, 'optionPrices', [])] + $calculatedOptionPrices;

        return (string)$this->json->encode($config);
    }
}
