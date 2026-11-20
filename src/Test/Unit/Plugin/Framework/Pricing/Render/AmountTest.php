<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Test\Unit\Plugin\Framework\Pricing\Render;

use Infrangible\CatalogProductPriceCalculation\Plugin\Framework\Pricing\Render\Amount;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\SaleableInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the CSS class of the prices of products.
 */
#[AllowMockObjectsWithoutExpectations]
class AmountTest extends TestCase
{
    /**
     * Tests that the class is added once to the prices of products.
     *
     * @return void
     */
    public function testBeforeToHtml(): void
    {
        $subject = $this->createAmount($this->createStub(Product::class), 'price-wrapper');

        $plugin = new Amount();
        $plugin->beforeToHtml($subject);
        $plugin->beforeToHtml($subject);

        $this->assertSame('price-wrapper price-calculation', $subject->getData('css_classes'));
    }

    /**
     * Tests that prices of other items are not changed.
     *
     * @return void
     */
    public function testBeforeToHtmlOfOtherItem(): void
    {
        $subject = $this->createAmount($this->createStub(SaleableInterface::class), null);

        (new Amount())->beforeToHtml($subject);

        $this->assertNull($subject->getData('css_classes'));
    }

    /**
     * Returns the renderer of an amount of the item with the CSS classes.
     *
     * @param SaleableInterface $saleableItem
     * @param string|null $cssClasses
     *
     * @return \Magento\Framework\Pricing\Render\Amount
     */
    private function createAmount(
        SaleableInterface $saleableItem,
        ?string $cssClasses
    ): \Magento\Framework\Pricing\Render\Amount {
        $amount = $this->getMockBuilder(\Magento\Framework\Pricing\Render\Amount::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSaleableItem'])
            ->getMock();

        $amount->method('getSaleableItem')->willReturn($saleableItem);

        if ($cssClasses !== null) {
            $amount->setData('css_classes', $cssClasses);
        }

        return $amount;
    }
}
