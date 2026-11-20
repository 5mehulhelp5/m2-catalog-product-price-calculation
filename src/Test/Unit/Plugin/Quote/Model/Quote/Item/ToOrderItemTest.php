<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Test\Unit\Plugin\Quote\Model\Quote\Item;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Infrangible\CatalogProductPriceCalculation\Plugin\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the calculation of the quote item is copied to the order item.
 */
#[AllowMockObjectsWithoutExpectations]
class ToOrderItemTest extends TestCase
{
    /**
     * Tests that the option is added to the product options of the order item.
     *
     * @return void
     */
    public function testAfterConvert(): void
    {
        $option = $this->createStub(Option::class);
        $option->method('getValue')->willReturn('12');

        $item = $this->createStub(Item::class);
        $item->method('getOptionByCode')->willReturnMap([[Data::QUOTE_ITEM_OPTION_CODE, $option]]);

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getProductOptions')->willReturn(['info_buyRequest' => []]);
        $orderItem->expects($this->once())->method('setProductOptions')->with(
            ['info_buyRequest' => [], Data::QUOTE_ITEM_OPTION_CODE => '12']
        );

        $this->assertSame(
            $orderItem,
            (new ToOrderItem())->afterConvert($this->createStub(Item\ToOrderItem::class), $orderItem, $item)
        );
    }

    /**
     * Tests that items without calculation are not changed.
     *
     * @return void
     */
    public function testAfterConvertWithoutCalculation(): void
    {
        $item = $this->createStub(Item::class);
        $item->method('getOptionByCode')->willReturn(null);

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->expects($this->never())->method('setProductOptions');

        (new ToOrderItem())->afterConvert($this->createStub(Item\ToOrderItem::class), $orderItem, $item);
    }
}
