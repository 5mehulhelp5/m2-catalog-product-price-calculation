<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Plugin\Quote\Model\Quote\Item;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Magento\Quote\Model\Quote\Address\Item as AddressItem;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Copies the calculation of the quote item to the product options of the order item.
 */
class ToOrderItem
{
    /**
     * Adds the calculation of the quote item as product option "price_calculation" of the order item.
     *
     * @param Item\ToOrderItem $subject
     * @param OrderItemInterface $result
     * @param Item|AddressItem $item
     *
     * @return OrderItemInterface
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterConvert(Item\ToOrderItem $subject, OrderItemInterface $result, $item): OrderItemInterface
    {
        if (! $item instanceof Item || ! $result instanceof OrderItem) {
            return $result;
        }

        $option = $item->getOptionByCode(Data::QUOTE_ITEM_OPTION_CODE);

        if (! $option instanceof Option) {
            return $result;
        }

        $value = $option->getValue();

        if (is_scalar($value) && (string)$value !== '') {
            // Magento declares an array, but returns null for order items without product options
            $options = (array)$result->getProductOptions();

            $options[ Data::QUOTE_ITEM_OPTION_CODE ] = (string)$value;

            $result->setProductOptions($options);
        }

        return $result;
    }
}
