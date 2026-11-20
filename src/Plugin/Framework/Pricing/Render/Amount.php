<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Plugin\Framework\Pricing\Render;

use Magento\Catalog\Model\Product;

/**
 * Adds the CSS class "price-calculation" to the prices of products.
 *
 * The setting "Avoid Price Jump" hides these prices until the calculated prices are shown.
 */
class Amount
{
    /** CSS class of the prices of products. */
    public const CSS_CLASS = 'price-calculation';

    /**
     * Adds the CSS class to the prices of products.
     *
     * @param \Magento\Framework\Pricing\Render\Amount $subject
     *
     * @return void
     */
    public function beforeToHtml(\Magento\Framework\Pricing\Render\Amount $subject): void
    {
        if (! $subject->getSaleableItem() instanceof Product) {
            return;
        }

        $cssClasses = $subject->getData('css_classes');
        $cssClasses = is_string($cssClasses) ? array_filter(explode(' ', $cssClasses)) : [];

        if (! in_array(self::CSS_CLASS, $cssClasses, true)) {
            $cssClasses[] = self::CSS_CLASS;
        }

        $subject->setData('css_classes', implode(' ', $cssClasses));
    }
}
