<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Model\Calculation;

use Infrangible\CatalogProductPriceCalculation\Model\Calculation\Prices\SimpleFactory;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationInterface;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Framework\Pricing\Amount\AmountInterface;

/**
 * Base class of calculations with a code and a priority.
 */
abstract class Base implements CalculationInterface
{
    /** @var string */
    private string $code = '';

    /** @var int */
    private int $priority = 0;

    /**
     * @param SimpleFactory $pricesFactory
     * @param AmountFactory $amountFactory
     */
    public function __construct(
        protected SimpleFactory $pricesFactory,
        protected AmountFactory $amountFactory
    ) {
    }

    /**
     * Returns the code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Sets the code.
     *
     * @param string $code
     *
     * @return void
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Sets the priority.
     *
     * @param int $priority
     *
     * @return void
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Creates an amount without adjustments, rounded to two decimals.
     *
     * @param float $value
     *
     * @return AmountInterface
     */
    protected function createAmount(float $value): AmountInterface
    {
        return $this->amountFactory->create(round($value, 2));
    }
}
