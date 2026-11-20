<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPriceCalculation\Observer;

use Infrangible\Core\Helper\Stores;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config;

/**
 * Adds the body class that hides prices until the calculated prices are shown (setting "Avoid Price Jump").
 */
class LayoutLoadBefore implements ObserverInterface
{
    /** Body class of the setting "Avoid Price Jump". */
    public const BODY_CLASS = 'price-calculation-visibility';

    /**
     * @param Stores $storeHelper
     * @param Config $pageConfig
     */
    public function __construct(
        protected Stores $storeHelper,
        protected Config $pageConfig
    ) {
    }

    /**
     * Adds the body class if the setting is active.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ($this->storeHelper->getStoreConfigFlag(
            'infrangible_catalogproductpricecalculation/general/avoid_price_jump'
        )) {
            $this->pageConfig->addBodyClass(self::BODY_CLASS);
        }
    }
}
