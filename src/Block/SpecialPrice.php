<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Block;

use Magento\ConfigurableProduct\Pricing\Render\FinalPriceBox;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\View\Element\Template;

/**
 * The regular price of configurable products with a special price in product lists.
 */
class SpecialPrice extends Template
{
    /**
     * Returns the price box of the configurable product.
     *
     * @return FinalPriceBox|null
     */
    public function getPriceBox(): ?FinalPriceBox
    {
        $priceBox = $this->_getData('price_box');

        return $priceBox instanceof FinalPriceBox ? $priceBox : null;
    }

    /**
     * Returns the price of the code from the price box.
     *
     * @param string $priceCode
     *
     * @return PriceInterface|null
     */
    public function getPriceType(string $priceCode): ?PriceInterface
    {
        return $this->getPriceBox()?->getPriceType($priceCode);
    }

    /**
     * Renders an amount with the price box.
     *
     * @param AmountInterface $amount
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     *
     * @return string
     */
    public function renderAmount(AmountInterface $amount, array $arguments = []): string
    {
        return $this->getPriceBox()?->renderAmount(
            $amount,
            $arguments
        ) ?? '';
    }

    /**
     * Returns the ID of the price element.
     *
     * @param string|null $defaultPrefix
     * @param string|null $defaultSuffix
     *
     * @return string
     */
    public function getPriceId(?string $defaultPrefix = null, ?string $defaultSuffix = null): string
    {
        return $this->getPriceBox()?->getPriceId(
            $defaultPrefix,
            $defaultSuffix
        ) ?? '';
    }

    /**
     * Returns the suffix of the IDs of the price box.
     *
     * @return string
     */
    public function getIdSuffix(): string
    {
        $idSuffix = $this->getPriceBox()?->getData('id_suffix');

        return is_string($idSuffix) ? $idSuffix : '';
    }
}
