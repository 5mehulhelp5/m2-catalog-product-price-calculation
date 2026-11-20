<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Model;

use Infrangible\CatalogProductPriceCalculation\Model\Calculation\PricesInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

/**
 * A calculation of product prices, for example a price of a customer; other modules add calculations with the event
 * "catalog_product_price_calculation".
 */
interface CalculationInterface
{
    /**
     * Returns the unique code of the calculation.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Returns the priority; the calculation with the highest priority is used.
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Returns the calculated prices of the product.
     *
     * @param Product $product
     *
     * @return PricesInterface
     * @throws LocalizedException
     */
    public function getProductPrices(Product $product): PricesInterface;

    /**
     * Returns the calculated tier prices of the product.
     *
     * @param Product $product
     *
     * @return PricesInterface[]
     * @throws LocalizedException
     */
    public function getProductTierPrices(Product $product): array;

    /**
     * Checks if the calculation has prices for the product.
     *
     * @param Product $product
     *
     * @return bool
     */
    public function hasProductCalculation(Product $product): bool;

    /**
     * Checks if the calculation is available for the current customer.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailableForProduct(): bool;

    /**
     * Checks if the quantity can be added to the cart with the calculated price.
     *
     * @param float $qty
     *
     * @return bool
     * @throws LocalizedException
     */
    public function hasProductQty(float $qty): bool;

    /**
     * Checks if the calculation is available for the quote item.
     *
     * @param Item $item
     * @param Item[] $calculatedItems the items that already have a calculated price
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailableForQuoteItem(Item $item, array $calculatedItems): bool;

    /**
     * Checks if the quantity of the quote item can have the calculated price.
     *
     * @param Item $item
     * @param Item[] $calculatedItems the items that already have a calculated price
     *
     * @return bool
     * @throws LocalizedException
     */
    public function hasQuoteItemQty(Item $item, array $calculatedItems): bool;

    /**
     * Returns the value of the quote item option "price_calculation", which is copied to the order item.
     *
     * @return string
     */
    public function getQuoteItemOptionCode(): string;
}
