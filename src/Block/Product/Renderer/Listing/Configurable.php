<?php /** @noinspection PhpDeprecationInspection The constructor of Magento's swatch renderer needs these classes. */

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
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;

/**
 * Renderer of configurable products in product lists, which adds the calculated prices to the price box.
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Listing\Configurable
{
    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param Data $helper
     * @param Product $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param Media $swatchMediaHelper
     * @param Json $json
     * @param Config $configHelper
     * @param array $data
     * @param SwatchAttributesProvider|null $swatchAttributesProvider
     * @param Format|null $localeFormat
     * @param Prices|null $variationPrices
     * @param Resolver|null $layerResolver
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        Product $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        protected Json $json,
        protected Config $configHelper,
        array $data = [],
        ?SwatchAttributesProvider $swatchAttributesProvider = null,
        ?Format $localeFormat = null,
        ?Prices $variationPrices = null,
        ?Resolver $layerResolver = null
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $swatchHelper,
            $swatchMediaHelper,
            $data,
            $swatchAttributesProvider,
            $localeFormat,
            $variationPrices,
            $layerResolver
        );
    }

    /**
     * Returns the configuration of the price box with the calculated prices as JSON.
     *
     * @return string
     */
    public function getPriceConfigJson(): string
    {
        $product = $this->getProduct();

        $config = $this->configHelper->processConfig(
            [
                'priceFormat' => $this->json->decode($this->getPriceFormatJson()),
                'prices'      => $this->json->decode($this->getPricesJson())
            ],
            $product
        );

        return (string)$this->json->encode($config);
    }
}
