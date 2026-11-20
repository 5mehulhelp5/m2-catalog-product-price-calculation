<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Model;

/**
 * The calculations that the observers of the event "catalog_product_price_calculation" add.
 */
class Calculations
{
    /** @var CalculationInterface[] */
    private array $calculations = [];

    /**
     * Returns the calculations.
     *
     * @return CalculationInterface[]
     */
    public function getCalculations(): array
    {
        return $this->calculations;
    }

    /**
     * Sets the calculations.
     *
     * @param CalculationInterface[] $calculations
     *
     * @return void
     */
    public function setCalculations(array $calculations): void
    {
        $this->calculations = array_values($calculations);
    }

    /**
     * Adds a calculation.
     *
     * @param CalculationInterface $calculation
     *
     * @return void
     */
    public function addCalculation(CalculationInterface $calculation): void
    {
        $this->calculations[] = $calculation;
    }
}
