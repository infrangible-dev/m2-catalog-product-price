<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Helper;

use Magento\Framework\Pricing\Render\Amount as AmountRender;

/**
 * The information texts of rendered price amounts by price type.
 */
class Amount
{
    /**
     * @param FinalPrice $finalPriceHelper
     * @param OldPrice $oldPriceHelper
     * @param MinPrice $minPriceHelper
     * @param MaxPrice $maxPriceHelper
     */
    public function __construct(
        protected FinalPrice $finalPriceHelper,
        protected OldPrice $oldPriceHelper,
        protected MinPrice $minPriceHelper,
        protected MaxPrice $maxPriceHelper
    ) {
    }

    /**
     * Returns the information text of the rendered price type as HTML.
     *
     * @param AmountRender $amount
     *
     * @return string
     */
    public function getInformation(AmountRender $amount): string
    {
        $valueHelper = $this->getValueHelper($amount->getData('price_type'));

        return $valueHelper === null ? '' : $valueHelper->getInformation($amount->getSaleableItem());
    }

    /**
     * Returns the helper of the price type, or null for other price types.
     *
     * @param mixed $priceType
     *
     * @return Value|null
     */
    public function getValueHelper(mixed $priceType): ?Value
    {
        return match ($priceType) {
            'finalPrice' => $this->finalPriceHelper,
            'oldPrice' => $this->oldPriceHelper,
            'minPrice' => $this->minPriceHelper,
            'maxPrice' => $this->maxPriceHelper,
            default => null
        };
    }
}
