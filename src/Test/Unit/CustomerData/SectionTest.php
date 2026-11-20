<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Test\Unit\CustomerData;

use Infrangible\CatalogProductPriceCalculation\CustomerData\Section;
use Infrangible\CatalogProductPriceCalculation\Helper\Data;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the private content with the active calculations.
 */
#[AllowMockObjectsWithoutExpectations]
class SectionTest extends TestCase
{
    /**
     * Tests that available calculations are active, and that a failing calculation is skipped.
     *
     * @return void
     */
    public function testGetSectionData(): void
    {
        $helper = $this->createStub(Data::class);
        $helper->method('getCalculations')->willReturn(
            [
                $this->createCalculation('available', true),
                $this->createCalculation('unavailable', false),
                $this->createCalculation('failing', null)
            ]
        );

        $this->assertSame(
            ['activeCalculationCodes' => ['available']],
            (new Section($helper, $this->createStub(LoggerInterface::class)))->getSectionData()
        );
    }

    /**
     * Returns a calculation that is available, not available or fails (null).
     *
     * @param string $code
     * @param bool|null $available
     *
     * @return CalculationInterface
     */
    private function createCalculation(string $code, ?bool $available): CalculationInterface
    {
        $calculation = $this->createStub(CalculationInterface::class);
        $calculation->method('getCode')->willReturn($code);

        if ($available === null) {
            $calculation->method('isAvailableForProduct')->willThrowException(new LocalizedException(__('failed')));
        } else {
            $calculation->method('isAvailableForProduct')->willReturn($available);
        }

        return $calculation;
    }
}
