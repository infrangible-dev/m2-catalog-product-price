<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPrice\Helper\FinalPrice;
use Infrangible\CatalogProductPrice\Helper\Information;
use Infrangible\CatalogProductPrice\Helper\OldPrice;
use Infrangible\CatalogProductPrice\Helper\Value;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;

/**
 * Adds the labels, information texts and the information line of the product and its variants to the JSON
 * configuration of the configurable product. Labels are plain text, information texts are HTML.
 */
class Configurable
{
    /**
     * @param FinalPrice $finalPriceHelper
     * @param Information $informationHelper
     * @param Json $json
     * @param OldPrice $oldPriceHelper
     * @param Variables $variables
     */
    public function __construct(
        protected FinalPrice $finalPriceHelper,
        protected Information $informationHelper,
        protected Json $json,
        protected OldPrice $oldPriceHelper,
        protected Variables $variables
    ) {
    }

    /**
     * Adds the values of the product and of its variants to the JSON configuration.
     *
     * @param Subject $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetJsonConfig(Subject $subject, string $result): string
    {
        $config = $this->json->decode($result);

        if (! is_array($config)) {
            return $result;
        }

        $prices = $this->addPrices(
            $config[ 'prices' ] ?? [],
            $subject->getProduct()
        );

        if ($prices !== []) {
            $config[ 'prices' ] = $prices;
        }

        $optionPrices = $config[ 'optionPrices' ] ?? [];

        if (! is_array($optionPrices)) {
            return $this->json->encode($config) ?? $result;
        }

        foreach ($subject->getAllowProducts() as $product) {
            if (! $product instanceof Product) {
                continue;
            }

            $productId = $this->variables->intValue($product->getId());

            $productPrices = $this->addPrices(
                $optionPrices[ $productId ] ?? [],
                $product
            );

            if ($productPrices !== []) {
                $optionPrices[ $productId ] = $productPrices;
            }
        }

        if ($optionPrices !== []) {
            $config[ 'optionPrices' ] = $optionPrices;
        }

        return $this->json->encode($config) ?? $result;
    }

    /**
     * Adds the labels and information texts of the final and old price and the information line.
     *
     * @param mixed $prices
     * @param Product $product
     *
     * @return array<string, mixed>
     */
    private function addPrices(mixed $prices, Product $product): array
    {
        if (! is_array($prices)) {
            $prices = [];
        }

        $prices = $this->addPrice(
            $prices,
            $product,
            $this->finalPriceHelper,
            'finalPrice'
        );

        $prices = $this->addPrice(
            $prices,
            $product,
            $this->oldPriceHelper,
            'oldPrice'
        );

        $information = [];

        $displayLabel = $this->informationHelper->getLabel(
            $product,
            ''
        );

        if ($displayLabel !== '') {
            $information[ 'displayLabel' ] = $displayLabel;
        }

        $price = $this->informationHelper->getPrice($product);

        if ($price !== null) {
            $information[ 'price' ] = $price->getValue();
        }

        $informationText = $this->informationHelper->getInformation($product);

        if ($informationText !== '') {
            $information[ 'information' ] = $informationText;
        }

        if ($information !== []) {
            $prices[ 'priceInformation' ] = $information;
        }

        return $prices;
    }

    /**
     * Adds label and information text of a price type, if any.
     *
     * @param array $prices
     * @phpstan-param array<string, mixed> $prices
     * @param Product $product
     * @param Value $valueHelper
     * @param string $priceType
     *
     * @return array<string, mixed>
     */
    private function addPrice(array $prices, Product $product, Value $valueHelper, string $priceType): array
    {
        $displayLabel = $valueHelper->getLabel(
            $product,
            ''
        );

        $information = $valueHelper->getInformation($product);

        if ($displayLabel === '' && $information === '') {
            return $prices;
        }

        $price = $prices[ $priceType ] ?? [];

        if (! is_array($price)) {
            $price = [];
        }

        if ($displayLabel !== '') {
            $price[ 'displayLabel' ] = $displayLabel;
        }

        if ($information !== '') {
            $price[ 'information' ] = $information;
        }

        $prices[ $priceType ] = $price;

        return $prices;
    }
}
