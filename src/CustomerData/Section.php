<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\CustomerData;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * The private content "catalog-product-price-calculation": the codes of the calculations of the current customer.
 */
class Section implements SectionSourceInterface
{
    /**
     * @param Data $helper
     * @param LoggerInterface $logging
     */
    public function __construct(
        protected Data $helper,
        protected LoggerInterface $logging
    ) {
    }

    /**
     * Returns the codes of the available calculations as "activeCalculationCodes".
     *
     * @return array
     * @phpstan-return array{activeCalculationCodes: string[]}
     */
    public function getSectionData(): array
    {
        $activeCalculationCodes = [];

        foreach ($this->helper->getCalculations() as $calculation) {
            try {
                if ($calculation->isAvailableForProduct()) {
                    $activeCalculationCodes[] = $calculation->getCode();
                }
            } catch (LocalizedException $exception) {
                $this->logging->error($exception);
            }
        }

        return ['activeCalculationCodes' => $activeCalculationCodes];
    }
}
