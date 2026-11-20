<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Block\Product\Renderer\Listing;

use Magento\Bundle\Pricing\Price\FinalPrice;
use Magento\Catalog\Model\Product;

/**
 * Renderer of bundle products in product lists, which adds the calculated prices to the price box.
 */
class Bundle extends AbstractRenderer
{
    /**
     * Returns the regular and final prices of the product, and its minimal and maximal price.
     *
     * @param Product $product
     *
     * @return array
     * @phpstan-return array<string, array{amount: float}>
     */
    protected function getPrices(Product $product): array
    {
        $prices = parent::getPrices($product);

        $finalPrice = $product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE);

        if ($finalPrice instanceof FinalPrice) {
            $prices[ 'minPrice' ] = ['amount' => $this->getNumber($finalPrice->getMinimalPrice()->getValue())];
            $prices[ 'maxPrice' ] = ['amount' => $this->getNumber($finalPrice->getMaximalPrice()->getValue())];
        }

        return $prices;
    }
}
