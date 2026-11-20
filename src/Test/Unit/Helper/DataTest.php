<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Test\Unit\Helper;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Infrangible\CatalogProductPriceCalculation\Model\Calculation\Prices\Simple;
use Infrangible\CatalogProductPriceCalculation\Model\Calculation\Prices\SimpleFactory;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationDataInterface;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationInterface;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationsFactory;
use Magento\Bundle\Pricing\Price\FinalPrice as BundleFinalPrice;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Amount\Base;
use Magento\Framework\Pricing\PriceInfo\Base as PriceInfo;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the calculation of prices and the custom prices of quote items.
 */
#[AllowMockObjectsWithoutExpectations]
class DataTest extends TestCase
{
    /**
     * Tests a discount: the final price is reduced with its adjustments, the old price is the final price.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testCalculatePricesWithDiscount(): void
    {
        $finalPrice = $this->createStub(FinalPrice::class);
        $finalPrice->method('getValue')->willReturn(100.);
        $finalPrice->method('getAmount')->willReturn(new Base(119., ['tax' => 19.]));

        $prices = $this->createHelper()->calculatePrices(
            $this->createProduct($finalPrice),
            $this->createCalculationData(null, 10)
        );

        $this->assertSame(107.1, $prices->getFinalPrice()->getValue());
        $this->assertEqualsWithDelta(17.1, $prices->getFinalPrice()->getAdjustmentAmount('tax'), 0.0001);
        $this->assertSame(90., $prices->getCatalogPrice());
        $this->assertSame(119., $prices->getOldPrice()?->getValue());
        $this->assertNull($prices->getMinimalPrice());
    }

    /**
     * Tests a fixed price: it is converted into the currency of the store and gets the adjustments of the display.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testCalculatePricesWithFixedPrice(): void
    {
        $finalPrice = $this->createStub(FinalPrice::class);
        $finalPrice->method('getAmount')->willReturn(new Base(119., ['tax' => 19.]));

        $prices = $this->createHelper(2.)->calculatePrices(
            $this->createProduct($finalPrice),
            $this->createCalculationData(40., null)
        );

        $this->assertSame(80., $prices->getCatalogPrice());
        $this->assertSame(95.2, $prices->getFinalPrice()->getValue());
        $this->assertSame(80., $prices->getFinalPrice()->getBaseAmount());
    }

    /**
     * Tests the minimal and maximal price of bundle products.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testCalculatePricesOfBundle(): void
    {
        $finalPrice = $this->createStub(BundleFinalPrice::class);
        $finalPrice->method('getValue')->willReturn(50.);
        $finalPrice->method('getAmount')->willReturn(new Base(50.));
        $finalPrice->method('getMinimalPrice')->willReturn(new Base(50.));
        $finalPrice->method('getMaximalPrice')->willReturn(new Base(80.));

        $helper = $this->createHelper();

        $prices = $helper->calculatePrices($this->createProduct($finalPrice), $this->createCalculationData(null, 50));

        $this->assertSame(25., $prices->getMinimalPrice()?->getValue());
        $this->assertSame(40., $prices->getMaximalPrice()?->getValue());

        $prices = $helper->calculatePrices($this->createProduct($finalPrice), $this->createCalculationData(30., null));

        $this->assertSame(35.7, $prices->getMinimalPrice()?->getValue());
        $this->assertSame(35.7, $prices->getMaximalPrice()?->getValue());
    }

    /**
     * Tests that a calculation without fixed price and discount is an error.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testCalculatePricesWithoutData(): void
    {
        $this->expectException(LocalizedException::class);

        $this->createHelper()->calculatePrices(
            $this->createStub(Product::class),
            $this->createCalculationData(null, null)
        );
    }

    /**
     * Tests that the calculation with the highest priority sets the custom price and the option of the item.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testUpdateItemPriceWithCalculations(): void
    {
        $prices = new Simple();
        $prices->setFinalPrice(new Base(9.52, ['tax' => 1.52]));
        $prices->setCatalogPrice(8.);

        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willReturn($this->createStub(Product::class));
        $item->expects($this->exactly(2))->method('setData')->willReturnCallback(
            function (string $key, mixed $value) use ($item): Item {
                $this->assertContains($key, ['custom_price', 'original_custom_price']);
                $this->assertSame(8., $value);

                return $item;
            }
        );
        $item->expects($this->once())->method('addOption');

        $this->assertTrue(
            $this->createHelper()->updateItemPriceWithCalculations(
                $item,
                [],
                [
                    $this->createCalculation('low', 1, true, new Simple()),
                    $this->createCalculation('high', 5, true, $prices),
                    $this->createCalculation('none', 9, false, new Simple())
                ]
            )
        );
    }

    /**
     * Tests that the custom price and the option are removed if no calculation applies any more.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testUpdateItemPriceRemovesCalculation(): void
    {
        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willReturn($this->createStub(Product::class));
        $item->method('getOptionByCode')->willReturn($this->createStub(Option::class));
        $item->expects($this->once())->method('removeOption')->with(Data::QUOTE_ITEM_OPTION_CODE);
        $item->expects($this->exactly(4))->method('setData');

        $this->assertFalse(
            $this->createHelper()->updateItemPriceWithCalculations(
                $item,
                [],
                [$this->createCalculation('none', 1, false, new Simple())]
            )
        );
    }

    /**
     * Returns a calculation.
     *
     * @param string $code
     * @param int $priority
     * @param bool $available
     * @param Simple $prices
     *
     * @return CalculationInterface
     */
    private function createCalculation(
        string $code,
        int $priority,
        bool $available,
        Simple $prices
    ): CalculationInterface {
        $calculation = $this->createStub(CalculationInterface::class);

        $calculation->method('getCode')->willReturn($code);
        $calculation->method('getPriority')->willReturn($priority);
        $calculation->method('hasProductCalculation')->willReturn($available);
        $calculation->method('isAvailableForQuoteItem')->willReturn(true);
        $calculation->method('hasQuoteItemQty')->willReturn(true);
        $calculation->method('getProductPrices')->willReturn($prices);
        $calculation->method('getQuoteItemOptionCode')->willReturn($code);

        return $calculation;
    }

    /**
     * Returns the data of a calculation.
     *
     * @param float|null $price
     * @param int|null $discount
     *
     * @return CalculationDataInterface
     */
    private function createCalculationData(?float $price, ?int $discount): CalculationDataInterface
    {
        $calculationData = $this->createStub(CalculationDataInterface::class);

        $calculationData->method('getPrice')->willReturn($price);
        $calculationData->method('getDiscount')->willReturn($discount);

        return $calculationData;
    }

    /**
     * Returns a product with the final price.
     *
     * @param FinalPrice $finalPrice
     *
     * @return Product
     */
    private function createProduct(FinalPrice $finalPrice): Product
    {
        $priceInfo = $this->createStub(PriceInfo::class);
        $priceInfo->method('getPrice')->willReturn($finalPrice);

        $product = $this->createStub(Product::class);
        $product->method('getPriceInfo')->willReturn($priceInfo);

        return $product;
    }

    /**
     * Returns the helper with a currency rate and a tax of 19 percent in the display.
     *
     * @param float $currencyRate
     *
     * @return Data
     */
    private function createHelper(float $currencyRate = 1.): Data
    {
        $pricesFactory = $this->createStub(SimpleFactory::class);
        $pricesFactory->method('create')->willReturnCallback(static fn(): Simple => new Simple());

        $amountFactory = $this->createStub(AmountFactory::class);
        $amountFactory->method('create')->willReturnCallback(
            static fn(float $amount, array $adjustmentAmounts = []): AmountInterface => new Base(
                $amount,
                $adjustmentAmounts
            )
        );

        $adjustmentCalculator = $this->createStub(CalculatorInterface::class);
        $adjustmentCalculator->method('getAmount')->willReturnCallback(
            static fn(float $amount): AmountInterface => new Base(round($amount * 1.19, 2), ['tax' => $amount * 0.19])
        );

        $priceCurrency = $this->createStub(PriceCurrency::class);
        $priceCurrency->method('convert')->willReturnCallback(
            static fn(float $amount): float => $amount * $currencyRate
        );
        $priceCurrency->method('roundPrice')->willReturnCallback(static fn(float $price): float => round($price, 2));

        return new Data(
            $this->createStub(CalculationsFactory::class),
            $this->createStub(ManagerInterface::class),
            $pricesFactory,
            $amountFactory,
            $adjustmentCalculator,
            $priceCurrency
        );
    }
}
