<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Helper;

use Infrangible\CatalogProductPriceCalculation\Model\Calculation\Prices\SimpleFactory;
use Infrangible\CatalogProductPriceCalculation\Model\Calculation\PricesInterface;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationDataInterface;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationInterface;
use Infrangible\CatalogProductPriceCalculation\Model\Calculations;
use Infrangible\CatalogProductPriceCalculation\Model\CalculationsFactory;
use Magento\Bundle\Pricing\Price\FinalPrice as BundleFinalPrice;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Collects the calculations of other modules, calculates prices and sets the calculated prices of quote items.
 */
class Data
{
    /** Code of the quote item option with the calculation of the item. */
    public const QUOTE_ITEM_OPTION_CODE = 'price_calculation';

    /** @var Calculations|null */
    private ?Calculations $calculations = null;

    /**
     * @param CalculationsFactory $calculationsFactory
     * @param ManagerInterface $eventManager
     * @param SimpleFactory $pricesFactory
     * @param AmountFactory $amountFactory
     * @param CalculatorInterface $adjustmentCalculator
     * @param PriceCurrency $priceCurrency
     */
    public function __construct(
        protected CalculationsFactory $calculationsFactory,
        protected ManagerInterface $eventManager,
        protected SimpleFactory $pricesFactory,
        protected AmountFactory $amountFactory,
        protected CalculatorInterface $adjustmentCalculator,
        protected PriceCurrency $priceCurrency
    ) {
    }

    /**
     * Returns the calculations that the observers of the event "catalog_product_price_calculation" add.
     *
     * @return CalculationInterface[]
     */
    public function getCalculations(): array
    {
        if ($this->calculations === null) {
            $this->calculations = $this->calculationsFactory->create();

            $this->eventManager->dispatch(
                'catalog_product_price_calculation',
                ['calculations' => $this->calculations]
            );
        }

        return $this->calculations->getCalculations();
    }

    /**
     * Returns the calculations sorted by priority, the highest first.
     *
     * @param CalculationInterface[]|null $calculations the calculations of the event if null
     *
     * @return CalculationInterface[]
     */
    public function getSortedCalculations(?array $calculations = null): array
    {
        $calculations ??= $this->getCalculations();

        usort(
            $calculations,
            static fn(CalculationInterface $calculation1, CalculationInterface $calculation2): int =>
                $calculation2->getPriority() <=> $calculation1->getPriority()
        );

        return $calculations;
    }

    /**
     * Returns the calculated prices of the product as configuration of the price box.
     *
     * @param CalculationInterface $calculation
     * @param Product $product
     *
     * @return array
     * @phpstan-return array<string, array{amount: float, adjustments: array<string, mixed>}>
     * @throws LocalizedException
     */
    public function getProductCalculation(CalculationInterface $calculation, Product $product): array
    {
        $calculationPrices = $calculation->getProductPrices($product);

        $calculationData = [
            'basePrice'  => $this->getPriceConfig($calculationPrices->getFinalPrice()->getBaseAmount()),
            'finalPrice' => $this->getPriceConfig($calculationPrices->getFinalPrice()->getValue())
        ];

        $oldPrice = $calculationPrices->getOldPrice();

        if ($oldPrice !== null) {
            $calculationData[ 'baseOldPrice' ] = $this->getPriceConfig($oldPrice->getBaseAmount());
            $calculationData[ 'oldPrice' ] = $this->getPriceConfig($oldPrice->getValue());
        }

        $minimalPrice = $calculationPrices->getMinimalPrice();

        if ($minimalPrice !== null) {
            $calculationData[ 'minPrice' ] = $this->getPriceConfig($minimalPrice->getValue());
        }

        $maximalPrice = $calculationPrices->getMaximalPrice();

        if ($maximalPrice !== null) {
            $calculationData[ 'maxPrice' ] = $this->getPriceConfig($maximalPrice->getValue());
        }

        return $calculationData;
    }

    /**
     * Returns a price of the configuration of the price box.
     *
     * @param float|null $amount
     *
     * @return array
     * @phpstan-return array{amount: float, adjustments: array<string, mixed>}
     */
    private function getPriceConfig(?float $amount): array
    {
        return ['amount' => (float)$amount, 'adjustments' => []];
    }

    /**
     * Returns the calculated tier prices of the product for quantities above 1.
     *
     * @param CalculationInterface $calculation
     * @param Product $product
     *
     * @return array
     * @phpstan-return array<int, array{qty: float, price: float, basePrice: float}>
     * @throws LocalizedException
     */
    public function getProductTierCalculation(CalculationInterface $calculation, Product $product): array
    {
        $tierPrices = [];

        foreach ($calculation->getProductTierPrices($product) as $calculationTierPrice) {
            if ($calculationTierPrice->getQty() > 1) {
                $tierPrices[] = [
                    'qty'       => $calculationTierPrice->getQty(),
                    'price'     => $calculationTierPrice->getFinalPrice()->getValue(),
                    'basePrice' => $calculationTierPrice->getFinalPrice()->getBaseAmount()
                ];
            }
        }

        return $tierPrices;
    }

    /**
     * Sets the calculated price of the calculation with the highest priority as custom price of the item.
     *
     * @param Item $item
     * @param Item[] $calculatedItems the items that already have a calculated price
     *
     * @return bool true if the item has a calculated price
     * @throws LocalizedException
     */
    public function updateItemPrice(Item $item, array $calculatedItems): bool
    {
        return $this->updateItemPriceWithCalculations($item, $calculatedItems, $this->getCalculations());
    }

    /**
     * Sets the calculated price of the calculation with the highest priority as custom price of the item.
     *
     * If no calculation applies any more, the calculated price is removed.
     *
     * @param Item $item
     * @param Item[] $calculatedItems the items that already have a calculated price
     * @param CalculationInterface[] $calculations
     *
     * @return bool true if the item has a calculated price
     * @throws LocalizedException
     */
    public function updateItemPriceWithCalculations(Item $item, array $calculatedItems, array $calculations): bool
    {
        if ($item->isDeleted()) {
            return false;
        }

        $product = $item->getProduct();

        foreach ($this->getSortedCalculations($calculations) as $calculation) {
            if ($calculation->hasProductCalculation($product) &&
                $calculation->isAvailableForQuoteItem($item, $calculatedItems) &&
                $calculation->hasQuoteItemQty($item, $calculatedItems)) {

                $prices = $calculation->getProductPrices($product);

                $this->setItemCustomPriceValue(
                    $item,
                    $prices->getCatalogPrice() ?? $prices->getFinalPrice()->getValue()
                );

                $item->addOption(
                    new DataObject(
                        [
                            'product' => $product,
                            'code'    => self::QUOTE_ITEM_OPTION_CODE,
                            'value'   => $calculation->getQuoteItemOptionCode()
                        ]
                    )
                );

                return true;
            }
        }

        if ($item->getOptionByCode(self::QUOTE_ITEM_OPTION_CODE) !== null) {
            $this->removeItemCustomPrice($item);

            $item->removeOption(self::QUOTE_ITEM_OPTION_CODE);
        }

        return false;
    }

    /**
     * Sets the value of the price as custom price of the item.
     *
     * @param Item $item
     * @param AmountInterface $price
     *
     * @return void
     */
    public function setItemCustomPrice(Item $item, AmountInterface $price): void
    {
        $this->setItemCustomPriceValue($item, $price->getValue());
    }

    /**
     * Sets the price as custom price of the item.
     *
     * @param Item $item
     * @param float $price
     *
     * @return void
     */
    public function setItemCustomPriceValue(Item $item, float $price): void
    {
        $item->setData('custom_price', $price);
        $item->setData('original_custom_price', $price);
    }

    /**
     * Removes the custom price of the item.
     *
     * @param Item $item
     *
     * @return void
     */
    public function removeItemCustomPrice(Item $item): void
    {
        // the values are set to null, so that the save removes them (unsetData() would keep them)
        $item->setData('custom_price');
        $item->setData('original_custom_price');
        $item->setData('calculation_price');
        $item->setData('base_calculation_price');
    }

    /**
     * Calculates the prices of the product with a fixed price or a discount on its final price.
     *
     * The prices are amounts in the currency of the store with the adjustments of the display (for example the tax),
     * like the prices of Magento. A fixed price is in the base currency. For bundle products, the minimal and maximal
     * price are calculated as well.
     *
     * @param Product $product
     * @param CalculationDataInterface $calculationData
     *
     * @return PricesInterface
     * @throws LocalizedException if the calculation has neither a fixed price nor a discount
     */
    public function calculatePrices(Product $product, CalculationDataInterface $calculationData): PricesInterface
    {
        $fixedPrice = $calculationData->getPrice();
        $discount = $calculationData->getDiscount();

        if ($fixedPrice === null && $discount === null) {
            throw new LocalizedException(__('The price calculation has neither a fixed price nor a discount.'));
        }

        $finalPrice = $product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE);
        $finalAmount = $finalPrice->getAmount();

        $prices = $this->pricesFactory->create();

        $prices->setOldPrice($finalAmount);

        if ($fixedPrice !== null) {
            $catalogPrice = $this->priceCurrency->roundPrice($this->priceCurrency->convert($fixedPrice));
            $amount = $this->adjustmentCalculator->getAmount($catalogPrice, $product);

            $prices->setCatalogPrice($catalogPrice);
            $prices->setFinalPrice($amount);

            if ($finalPrice instanceof BundleFinalPrice) {
                $prices->setMinimalPrice($amount);
                $prices->setMaximalPrice($amount);
            }

            return $prices;
        }

        $factor = (100 - max(0, min(100, $discount))) / 100;

        $prices->setCatalogPrice($this->priceCurrency->roundPrice($finalPrice->getValue() * $factor));
        $prices->setFinalPrice($this->scaleAmount($finalAmount, $factor));

        if ($finalPrice instanceof BundleFinalPrice) {
            $prices->setMinimalPrice($this->scaleAmount($finalPrice->getMinimalPrice(), $factor));
            $prices->setMaximalPrice($this->scaleAmount($finalPrice->getMaximalPrice(), $factor));
        }

        return $prices;
    }

    /**
     * Returns the amount multiplied with the factor, with its adjustments.
     *
     * @param AmountInterface $amount
     * @param float $factor
     *
     * @return AmountInterface
     */
    public function scaleAmount(AmountInterface $amount, float $factor): AmountInterface
    {
        return $this->amountFactory->create(
            $this->priceCurrency->roundPrice($amount->getValue() * $factor),
            array_map(
                static fn(float $adjustmentAmount): float => $adjustmentAmount * $factor,
                $amount->getAdjustmentAmounts()
            )
        );
    }
}
