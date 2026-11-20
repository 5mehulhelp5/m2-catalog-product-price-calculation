<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Block;

use FeWeDev\Base\Json;
use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Framework\View\Element\Template;

/**
 * Initializes the component that activates the calculations of the customer on every page.
 */
class Section extends Template
{
    /**
     * @param Template\Context $context
     * @param Data $helper
     * @param Json $json
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        protected Data $helper,
        protected Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns the priorities of the calculations by code as JSON object.
     *
     * @return string
     */
    public function getCalculationsConfig(): string
    {
        $calculationConfig = [];

        foreach ($this->helper->getCalculations() as $calculation) {
            $calculationConfig[ $calculation->getCode() ] = $calculation->getPriority();
        }

        return count($calculationConfig) > 0 ? (string)$this->json->encode($calculationConfig) : '{}';
    }
}
