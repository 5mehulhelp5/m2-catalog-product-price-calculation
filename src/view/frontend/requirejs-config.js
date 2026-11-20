/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

const config = {
    map: {
        '*': {
            priceBox: 'Infrangible_CatalogProductPriceCalculation/js/price-box'
        }
    },
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'Infrangible_CatalogProductPriceCalculation/js/swatch-renderer-mixin': true
            },
            'Amasty_Conf/js/swatch-renderer': {
                'Infrangible_CatalogProductPriceCalculation/js/swatch-renderer-mixin': true
            }
        }
    }
};
