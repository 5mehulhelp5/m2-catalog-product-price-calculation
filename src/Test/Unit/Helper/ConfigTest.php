<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Test\Unit\Helper;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPriceCalculation\Helper\Config;
use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationInterface;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the calculated prices in the configuration of the price boxes.
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigTest extends TestCase
{
    /**
     * Tests the saved percentage of tier prices, also without a price.
     *
     * @return void
     */
    public function testCalculateSavePercent(): void
    {
        $config = $this->createConfig($this->createStub(Data::class));

        $this->assertSame(20., $config->calculateSavePercent(50., 40.));
        $this->assertSame(0., $config->calculateSavePercent(0., 40.));
    }

    /**
     * Tests that the prices of the calculations are added with the default prices, and that a failing calculation is
     * skipped.
     *
     * @return void
     */
    public function testProcessConfig(): void
    {
        $calculation = $this->createStub(CalculationInterface::class);
        $calculation->method('getCode')->willReturn('customer');
        $calculation->method('hasProductCalculation')->willReturn(true);

        $failingCalculation = $this->createStub(CalculationInterface::class);
        $failingCalculation->method('getCode')->willReturn('failing');
        $failingCalculation->method('hasProductCalculation')->willReturn(true);

        $helper = $this->createStub(Data::class);
        $helper->method('getCalculations')->willReturn([$calculation, $failingCalculation]);
        $helper->method('getProductCalculation')->willReturnCallback(
            static function (CalculationInterface $calculation) use ($failingCalculation): array {
                if ($calculation === $failingCalculation) {
                    throw new LocalizedException(__('failed'));
                }

                return ['finalPrice' => ['amount' => 50., 'adjustments' => []]];
            }
        );
        $helper->method('getProductTierCalculation')->willReturn([['qty' => 5., 'price' => 40., 'basePrice' => 40.]]);

        /** @var array{calculatedPrices: array<string, mixed>, calculatedTierPrices: array<string, array<int, array<string, mixed>>>} $result */
        $result = $this->createConfig($helper)->processConfig(
            ['prices' => ['finalPrice' => ['amount' => 60.]]],
            $this->createStub(\Magento\Catalog\Model\Product::class)
        );

        $this->assertSame(['default', 'customer'], array_keys($result[ 'calculatedPrices' ]));
        $this->assertSame(['finalPrice' => ['amount' => 60.]], $result[ 'calculatedPrices' ][ 'default' ]);
        $this->assertSame(
            ['value' => 20., 'formatted' => '20'],
            $result[ 'calculatedTierPrices' ][ 'customer' ][ 0 ][ 'savePercent' ]
        );
        $this->assertSame([], $result[ 'calculatedTierPrices' ][ 'default' ]);
    }

    /**
     * Returns the configuration helper.
     *
     * @param Data $helper
     *
     * @return Config
     */
    private function createConfig(Data $helper): Config
    {
        $productHelper = $this->createStub(Product::class);
        $productHelper->method('getTierPricesByProduct')->willReturn([]);

        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('formatNumber')->willReturnCallback(
            static fn(float $value, int $precision): string => number_format($value, $precision)
        );

        return new Config(
            $helper,
            $productHelper,
            new Arrays(new Variables()),
            $storeHelper,
            $this->createStub(LoggerInterface::class)
        );
    }
}
