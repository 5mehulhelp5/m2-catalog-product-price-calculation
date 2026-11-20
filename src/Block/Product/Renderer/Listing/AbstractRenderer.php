<?php /** @noinspection PhpDeprecationInspection Magento's renderers of product lists build on these classes. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Block\Product\Renderer\Listing;

use FeWeDev\Base\Json;
use Infrangible\CatalogProductPriceCalculation\Helper\Config;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View\AbstractView;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\Locale\Format;
use Magento\Framework\Stdlib\ArrayUtils;

/**
 * Renderer of the product lists that adds the calculated prices to the price box of a product.
 */
abstract class AbstractRenderer extends AbstractView
{
    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param Json $json
     * @param Format $localeFormat
     * @param Config $configHelper
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        protected Json $json,
        protected Format $localeFormat,
        protected Config $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $arrayUtils, $data);
    }

    /**
     * Returns the product of the list that the renderer renders (set by the list), or null.
     *
     * @return Product|null
     */
    public function getListProduct(): ?Product
    {
        $product = $this->getData('product');

        return $product instanceof Product ? $product : null;
    }

    /**
     * Returns the configuration of the price box with the calculated prices as JSON.
     *
     * @return string
     */
    public function getPriceConfigJson(): string
    {
        $product = $this->getListProduct();

        if ($product === null) {
            return '{}';
        }

        $config = $this->configHelper->processConfig(
            [
                'priceFormat' => $this->localeFormat->getPriceFormat(),
                'prices'      => $this->getPrices($product)
            ],
            $product
        );

        return (string)$this->json->encode($config);
    }

    /**
     * Returns the regular and final prices of the product.
     *
     * @param Product $product
     *
     * @return array
     * @phpstan-return array<string, array{amount: float}>
     */
    protected function getPrices(Product $product): array
    {
        $priceInfo = $product->getPriceInfo();

        $regularAmount = $priceInfo->getPrice(RegularPrice::PRICE_CODE)->getAmount();
        $finalAmount = $priceInfo->getPrice(FinalPrice::PRICE_CODE)->getAmount();

        return [
            'baseOldPrice' => ['amount' => $this->getNumber($regularAmount->getBaseAmount())],
            'oldPrice'     => ['amount' => $this->getNumber($regularAmount->getValue())],
            'basePrice'    => ['amount' => $this->getNumber($finalAmount->getBaseAmount())],
            'finalPrice'   => ['amount' => $this->getNumber($finalAmount->getValue())]
        ];
    }

    /**
     * Returns the amount as number.
     *
     * @param float|null $amount
     *
     * @return float
     */
    protected function getNumber(?float $amount): float
    {
        return (float)$this->localeFormat->getNumber($amount ?? 0);
    }
}
