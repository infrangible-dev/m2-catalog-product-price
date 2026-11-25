<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Block;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;

/**
 * The information line before a price: label, price and information text.
 */
class Information extends Template
{
    /**
     * @param Template\Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        protected PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Formats an amount that is already in the currency of the store.
     *
     * @param float $amount
     * @param bool $includeContainer
     * @param int $precision
     *
     * @return string
     */
    public function formatCurrency(
        float $amount,
        bool $includeContainer = true,
        int $precision = PriceCurrencyInterface::DEFAULT_PRECISION
    ): string {
        return $this->priceCurrency->format(
            $amount,
            $includeContainer,
            $precision
        );
    }
}
