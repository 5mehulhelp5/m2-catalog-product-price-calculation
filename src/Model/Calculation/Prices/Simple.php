<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Model\Calculation\Prices;

use Infrangible\CatalogProductPriceCalculation\Model\Calculation\PricesInterface;
use LogicException;
use Magento\Framework\Pricing\Amount\AmountInterface;

/**
 * Calculated prices with setters.
 */
class Simple implements PricesInterface
{
    /** @var float */
    private float $qty = 1.;

    /** @var AmountInterface|null */
    private ?AmountInterface $finalPrice = null;

    /** @var AmountInterface|null */
    private ?AmountInterface $oldPrice = null;

    /** @var AmountInterface|null */
    private ?AmountInterface $minimalPrice = null;

    /** @var AmountInterface|null */
    private ?AmountInterface $maximalPrice = null;

    /** @var float|null */
    private ?float $catalogPrice = null;

    /**
     * Returns the quantity.
     *
     * @return float
     */
    public function getQty(): float
    {
        return $this->qty;
    }

    /**
     * Sets the quantity.
     *
     * @param float $qty
     *
     * @return void
     */
    public function setQty(float $qty): void
    {
        $this->qty = $qty;
    }

    /**
     * Returns the final price.
     *
     * @return AmountInterface
     * @throws LogicException if the final price was not set
     */
    public function getFinalPrice(): AmountInterface
    {
        if ($this->finalPrice === null) {
            throw new LogicException('The calculated prices have no final price');
        }

        return $this->finalPrice;
    }

    /**
     * Sets the final price.
     *
     * @param AmountInterface $finalPrice
     *
     * @return void
     */
    public function setFinalPrice(AmountInterface $finalPrice): void
    {
        $this->finalPrice = $finalPrice;
    }

    /**
     * Returns the price before the calculation.
     *
     * @return AmountInterface|null
     */
    public function getOldPrice(): ?AmountInterface
    {
        return $this->oldPrice;
    }

    /**
     * Sets the price before the calculation.
     *
     * @param AmountInterface|null $oldPrice
     *
     * @return void
     */
    public function setOldPrice(?AmountInterface $oldPrice = null): void
    {
        $this->oldPrice = $oldPrice;
    }

    /**
     * Returns the minimal price of bundle products.
     *
     * @return AmountInterface|null
     */
    public function getMinimalPrice(): ?AmountInterface
    {
        return $this->minimalPrice;
    }

    /**
     * Sets the minimal price of bundle products.
     *
     * @param AmountInterface|null $minimalPrice
     *
     * @return void
     */
    public function setMinimalPrice(?AmountInterface $minimalPrice): void
    {
        $this->minimalPrice = $minimalPrice;
    }

    /**
     * Returns the maximal price of bundle products.
     *
     * @return AmountInterface|null
     */
    public function getMaximalPrice(): ?AmountInterface
    {
        return $this->maximalPrice;
    }

    /**
     * Sets the maximal price of bundle products.
     *
     * @param AmountInterface|null $maximalPrice
     *
     * @return void
     */
    public function setMaximalPrice(?AmountInterface $maximalPrice): void
    {
        $this->maximalPrice = $maximalPrice;
    }

    /**
     * Returns the calculated price as catalog price in the currency of the store, or null if it is not known.
     *
     * The catalog price has no adjustments of the display, like the custom price of a quote item.
     *
     * @return float|null
     */
    public function getCatalogPrice(): ?float
    {
        return $this->catalogPrice;
    }

    /**
     * Sets the calculated price as catalog price in the currency of the store.
     *
     * @param float|null $catalogPrice
     *
     * @return void
     */
    public function setCatalogPrice(?float $catalogPrice): void
    {
        $this->catalogPrice = $catalogPrice;
    }
}
