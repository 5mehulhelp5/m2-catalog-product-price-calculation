<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Test\Unit\Observer;

use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationInterface;
use Infrangible\CatalogProductPriceCalculation\Observer\CheckoutCartProductAddBefore;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the check of the quantity that is added to the cart.
 */
#[AllowMockObjectsWithoutExpectations]
class CheckoutCartProductAddBeforeTest extends TestCase
{
    /**
     * Returns request infos and the quantity that the calculation gets.
     *
     * @return array
     * @phpstan-return array<string, array{mixed, float}>
     */
    public static function requestInfoProvider(): array
    {
        return [
            'array'       => [['qty' => '3'], 3.],
            'data object' => [new DataObject(['qty' => 2]), 2.],
            'number'      => [4, 4.],
            'no quantity' => [['product' => 1], 1.],
            'invalid'     => [['qty' => 'x'], 1.]
        ];
    }

    /**
     * Tests the quantity of the request info.
     *
     * @dataProvider requestInfoProvider
     *
     * @param mixed $requestInfo
     * @param float $expectedQty
     *
     * @return void
     * @throws LocalizedException
     */
    #[DataProvider('requestInfoProvider')]
    public function testExecuteQty(mixed $requestInfo, float $expectedQty): void
    {
        $calculation = $this->createMock(CalculationInterface::class);
        $calculation->method('hasProductCalculation')->willReturn(true);
        $calculation->method('isAvailableForProduct')->willReturn(true);
        $calculation->expects($this->once())->method('hasProductQty')->with($expectedQty)->willReturn(true);

        $this->createObserver($calculation)->execute($this->createEvent($requestInfo));
    }

    /**
     * Tests that a quantity that the calculation does not allow is an error.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testExecuteNotAvailable(): void
    {
        $calculation = $this->createStub(CalculationInterface::class);
        $calculation->method('hasProductCalculation')->willReturn(true);
        $calculation->method('isAvailableForProduct')->willReturn(true);
        $calculation->method('hasProductQty')->willReturn(false);

        $this->expectException(LocalizedException::class);

        $this->createObserver($calculation)->execute($this->createEvent(['qty' => 5]));
    }

    /**
     * Returns the event of adding the product to the cart.
     *
     * @param mixed $requestInfo
     *
     * @return Observer
     */
    private function createEvent(mixed $requestInfo): Observer
    {
        return new Observer(['product' => $this->createStub(Product::class), 'info' => $requestInfo]);
    }

    /**
     * Returns the observer with the calculation.
     *
     * @param CalculationInterface $calculation
     *
     * @return CheckoutCartProductAddBefore
     */
    private function createObserver(CalculationInterface $calculation): CheckoutCartProductAddBefore
    {
        $helper = $this->createStub(Data::class);
        $helper->method('getSortedCalculations')->willReturn([$calculation]);

        return new CheckoutCartProductAddBefore($helper);
    }
}
