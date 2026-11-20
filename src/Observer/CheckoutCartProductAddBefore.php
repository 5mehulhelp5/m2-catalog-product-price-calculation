<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Observer;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Prevents that more products are added to the cart than a calculation of the customer allows.
 */
class CheckoutCartProductAddBefore implements ObserverInterface
{
    /**
     * @param Data $calculationHelper
     */
    public function __construct(protected Data $calculationHelper)
    {
    }

    /**
     * Throws an exception if an available calculation of the product does not allow the quantity.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getData('product');

        if (! $product instanceof Product) {
            return;
        }

        $qty = $this->getQty($observer->getData('info'));

        foreach ($this->calculationHelper->getSortedCalculations() as $calculation) {
            if ($calculation->hasProductCalculation($product) && $calculation->isAvailableForProduct() &&
                ! $calculation->hasProductQty($qty)) {

                throw new LocalizedException(__('Not all of your products are available in the requested quantity.'));
            }
        }
    }

    /**
     * Returns the quantity of the request: a number, an array or a data object with "qty", 1 by default.
     *
     * @param mixed $requestInfo
     *
     * @return float
     */
    private function getQty(mixed $requestInfo): float
    {
        if ($requestInfo instanceof DataObject) {
            $requestInfo = $requestInfo->getData();
        }

        $qty = is_array($requestInfo) ? $requestInfo[ 'qty' ] ?? null : $requestInfo;

        return is_numeric($qty) && $qty > 0 ? (float)$qty : 1.;
    }
}
