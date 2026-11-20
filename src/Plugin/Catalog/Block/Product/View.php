<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Plugin\Catalog\Block\Product;

use FeWeDev\Base\Json;
use Infrangible\CatalogProductPriceCalculation\Helper\Config;
use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Pricing\Price\FinalPrice as BundleFinalPrice;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;

/**
 * Adds the calculated prices to the price configuration of the product page.
 */
class View
{
    /**
     * @param Json $json
     * @param Config $configHelper
     */
    public function __construct(
        protected Json $json,
        protected Config $configHelper
    ) {
    }

    /**
     * Adds the prices of the product without options and the calculated prices.
     *
     * @param \Magento\Catalog\Block\Product\View $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetJsonConfig(\Magento\Catalog\Block\Product\View $subject, string $result): string
    {
        $config = $this->json->decode($result);
        $product = $subject->getProduct();

        if (! is_array($config)) {
            return $result;
        }

        $priceInfo = $product->getPriceInfo();

        if (! $subject->hasOptions()) {
            $regularAmount = $priceInfo->getPrice(RegularPrice::PRICE_CODE)->getAmount();
            $finalAmount = $priceInfo->getPrice(FinalPrice::PRICE_CODE)->getAmount();

            $config[ 'prices' ] = [
                'baseOldPrice' => ['amount' => $regularAmount->getBaseAmount(), 'adjustments' => []],
                'oldPrice'     => ['amount' => $regularAmount->getValue(), 'adjustments' => []],
                'basePrice'    => ['amount' => $finalAmount->getBaseAmount(), 'adjustments' => []],
                'finalPrice'   => ['amount' => $finalAmount->getValue(), 'adjustments' => []]
            ];
        }

        $finalPrice = $priceInfo->getPrice(FinalPrice::PRICE_CODE);

        if ($product->getTypeId() === Type::TYPE_CODE && $finalPrice instanceof BundleFinalPrice) {
            $prices = is_array($config[ 'prices' ] ?? null) ? $config[ 'prices' ] : [];

            $prices[ 'minPrice' ] = ['amount' => $finalPrice->getMinimalPrice()->getValue(), 'adjustments' => []];
            $prices[ 'maxPrice' ] = ['amount' => $finalPrice->getMaximalPrice()->getValue(), 'adjustments' => []];

            $config[ 'prices' ] = $prices;
        }

        /** @var array<string, mixed> $config */
        return (string)$this->json->encode($this->configHelper->processConfig($config, $product));
    }
}
