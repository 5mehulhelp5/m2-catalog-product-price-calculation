<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Model\Calculation;

use Magento\Framework\Pricing\Amount\AmountInterface;

/**
 * The calculated prices of a product for a quantity.
 */
interface PricesInterface
{
    /**
     * Returns the quantity of the prices.
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Returns the calculated final price.
     *
     * @return AmountInterface
     */
    public function getFinalPrice(): AmountInterface;

    /**
     * Returns the price before the calculation, or null.
     *
     * @return AmountInterface|null
     */
    public function getOldPrice(): ?AmountInterface;

    /**
     * Returns the minimal price of bundle products, or null.
     *
     * @return AmountInterface|null
     */
    public function getMinimalPrice(): ?AmountInterface;

    /**
     * Returns the maximal price of bundle products, or null.
     *
     * @return AmountInterface|null
     */
    public function getMaximalPrice(): ?AmountInterface;

    /**
     * Returns the calculated price as catalog price in the currency of the store, or null if it is not known.
     *
     * The catalog price has no adjustments of the display, like the custom price of a quote item.
     *
     * @return float|null
     */
    public function getCatalogPrice(): ?float;
}
