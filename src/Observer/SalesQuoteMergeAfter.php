<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Observer;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Sets the calculated prices of the items after the cart of a guest was merged into the cart of the customer.
 */
class SalesQuoteMergeAfter implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Sets the calculated prices of the items of the merged quote.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getData('quote');

        if (! $quote instanceof Quote) {
            return;
        }

        $calculatedItems = [];

        foreach ($quote->getItemsCollection() as $item) {
            if ($item instanceof Item && $this->helper->updateItemPrice($item, $calculatedItems)) {
                $calculatedItems[] = $item;
            }
        }
    }
}
