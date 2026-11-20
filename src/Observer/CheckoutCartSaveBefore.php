<?php /** @noinspection PhpDeprecationInspection The event of Magento passes the deprecated cart. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Observer;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

/**
 * Sets the calculated prices of the items before the cart is saved.
 */
class CheckoutCartSaveBefore implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Sets the calculated prices of the items of the cart.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $cart = $observer->getData('cart');

        if (! $cart instanceof Cart) {
            return;
        }

        $quote = $cart->getQuote();

        $items = $quote->getId() ? $cart->getItems() : $quote->getItemsCollection();

        $calculatedItems = [];

        foreach ($items as $item) {
            if ($item instanceof Item && $this->helper->updateItemPrice($item, $calculatedItems)) {
                $calculatedItems[] = $item;
            }
        }
    }
}
