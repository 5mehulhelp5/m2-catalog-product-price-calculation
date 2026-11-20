<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Model;

/**
 * The data of a calculation that Helper\Data::calculatePrices() uses: a fixed price or a discount in percent.
 */
interface CalculationDataInterface
{
    /**
     * Returns the fixed price in the base currency, or null for a discount.
     *
     * @return float|null
     */
    public function getPrice(): ?float;

    /**
     * Returns the discount in percent, or null for a fixed price.
     *
     * @return int|null
     */
    public function getDiscount(): ?int;
}
