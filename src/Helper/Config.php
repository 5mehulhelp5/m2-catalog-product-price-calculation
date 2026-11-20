<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Helper;

use FeWeDev\Base\Arrays;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Adds the calculated prices to the configuration of the price boxes.
 */
class Config
{
    /**
     * @param Data $helper
     * @param Product $productHelper
     * @param Arrays $arrays
     * @param Stores $storeHelper
     * @param LoggerInterface $logging
     */
    public function __construct(
        protected Data $helper,
        protected Product $productHelper,
        protected Arrays $arrays,
        protected Stores $storeHelper,
        protected LoggerInterface $logging
    ) {
    }

    /**
     * Adds the prices and tier prices of the calculations of the product as "calculatedPrices" and
     * "calculatedTierPrices", with the prices of the product as calculation "default".
     *
     * A calculation that fails is skipped and logged.
     *
     * @param array $config
     * @param \Magento\Catalog\Model\Product $product
     * @phpstan-param array<string, mixed> $config
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    public function processConfig(array $config, \Magento\Catalog\Model\Product $product): array
    {
        $config[ 'calculatedPrices' ] = [
            'default' => $this->arrays->getValue($config, 'prices', [])
        ];

        $config[ 'calculatedTierPrices' ] = [
            'default' => $this->arrays->getValue(
                $config,
                'tierPrices',
                $this->productHelper->getTierPricesByProduct($product)
            )
        ];

        foreach ($this->helper->getCalculations() as $calculation) {
            if (! $calculation->hasProductCalculation($product)) {
                continue;
            }

            $calculationCode = $calculation->getCode();

            try {
                $calculatedPrices = $this->helper->getProductCalculation($calculation, $product);
                $calculatedTierPrices = $this->helper->getProductTierCalculation($calculation, $product);
            } catch (LocalizedException $exception) {
                $this->logging->error($exception);

                continue;
            }

            $config[ 'calculatedPrices' ][ $calculationCode ] = $calculatedPrices;
            $config[ 'calculatedTierPrices' ][ $calculationCode ] = $this->addSavePercent(
                $calculatedTierPrices,
                $calculatedPrices[ 'finalPrice' ][ 'amount' ]
            );
        }

        return $config;
    }

    /**
     * Adds the percentage that each tier price saves compared to the price as "savePercent".
     *
     * @param array $tierPrices
     * @param float $productPriceValue
     * @phpstan-param array<int, array{qty: float, price: float, basePrice: float}> $tierPrices
     *
     * @return array
     * @phpstan-return array<int, array<string, mixed>>
     */
    private function addSavePercent(array $tierPrices, float $productPriceValue): array
    {
        foreach ($tierPrices as $key => $tierPrice) {
            $savePercent = $this->calculateSavePercent($productPriceValue, $tierPrice[ 'price' ]);

            $tierPrices[ $key ][ 'savePercent' ] = [
                'value'     => $savePercent,
                'formatted' => $this->getFormattedSavePercent($savePercent)
            ];
        }

        return $tierPrices;
    }

    /**
     * Returns the percentage that the tier price saves compared to the price, rounded, or 0 without a price.
     *
     * @param float $productPriceValue
     * @param float $tierPriceValue
     *
     * @return float
     */
    public function calculateSavePercent(float $productPriceValue, float $tierPriceValue): float
    {
        if ($productPriceValue <= 0) {
            return 0.;
        }

        return round(100 - ((100 / $productPriceValue) * $tierPriceValue));
    }

    /**
     * Returns the percentage formatted for the store view, without decimals.
     *
     * @param float $savePercent
     *
     * @return string
     */
    public function getFormattedSavePercent(float $savePercent): string
    {
        return $this->storeHelper->formatNumber(round($savePercent), 0);
    }
}
