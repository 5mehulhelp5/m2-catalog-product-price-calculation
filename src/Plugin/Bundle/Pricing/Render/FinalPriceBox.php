<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Plugin\Bundle\Pricing\Render;

use Magento\Catalog\Model\Product;

/**
 * Renders the price of bundle products without price range with the template of the module, which the calculations
 * can update.
 */
class FinalPriceBox
{
    /** Template of the price of bundle products. */
    public const TEMPLATE = 'Infrangible_CatalogProductPriceCalculation::bundle/price/final_price.phtml';

    /**
     * Sets the template of the module for bundle products without price view and price range.
     *
     * @param \Magento\Bundle\Pricing\Render\FinalPriceBox $subject
     *
     * @return void
     */
    public function beforeToHtml(\Magento\Bundle\Pricing\Render\FinalPriceBox $subject): void
    {
        $product = $subject->getSaleableItem();

        if ($product instanceof Product && ! $product->getData('price_view') && ! $subject->showRangePrice()) {
            $subject->setTemplate(self::TEMPLATE);
        }
    }
}
