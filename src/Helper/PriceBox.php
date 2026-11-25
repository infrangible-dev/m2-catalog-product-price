<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Helper;

use Exception;
use Infrangible\CatalogProductPrice\Block\Information as InformationBlock;
use Infrangible\CatalogProductPrice\Block\SpecialPrice;
use Infrangible\Core\Helper\Block;
use Infrangible\Core\Helper\Stores;
use Magento\ConfigurableProduct\Pricing\Render\FinalPriceBox;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Render\PriceBox as PriceBoxRender;

/**
 * Labels and the information line of price boxes.
 */
class PriceBox
{
    /**
     * @param Amount $amountHelper
     * @param Information $informationHelper
     * @param Block $blockHelper
     * @param Stores $storeHelper
     */
    public function __construct(
        protected Amount $amountHelper,
        protected Information $informationHelper,
        protected Block $blockHelper,
        protected Stores $storeHelper
    ) {
    }

    /**
     * Replaces the label in the arguments of a rendered amount with the label of its price type.
     *
     * @param PriceBoxRender $priceBox
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function updateDisplayLabel(PriceBoxRender $priceBox, array $arguments): array
    {
        $valueHelper = $this->amountHelper->getValueHelper($arguments[ 'price_type' ] ?? null);

        if ($valueHelper !== null) {
            $displayLabel = $arguments[ 'display_label' ] ?? '';

            $arguments[ 'display_label' ] = $valueHelper->getLabel(
                $priceBox->getSaleableItem(),
                $displayLabel instanceof Phrase || is_string($displayLabel) ? $displayLabel : ''
            );
        }

        return $arguments;
    }

    /**
     * Renders the information line of the final price and of prices in product lists.
     *
     * @param PriceBoxRender $priceBox
     *
     * @return string
     */
    public function getInformationHtml(PriceBoxRender $priceBox): string
    {
        if ($priceBox->getData('price_type_code') !== 'final_price' && $priceBox->getData('zone') !== 'item_list') {
            return '';
        }

        $templateData = $this->informationHelper->getData($priceBox->getSaleableItem());

        if (count($templateData) === 0) {
            return '';
        }

        $templateData[ 'include_container' ] = true;

        return $this->blockHelper->renderChildTemplateExtendedBlock(
            $priceBox,
            InformationBlock::class,
            'Infrangible_CatalogProductPrice::information.phtml',
            $templateData
        );
    }

    /**
     * Renders the regular price of configurable products with a special price in product lists.
     *
     * @param FinalPriceBox $priceBox
     *
     * @return string
     * @throws Exception
     */
    public function getSpecialPriceHtml(FinalPriceBox $priceBox): string
    {
        $enabled = $this->storeHelper->getStoreConfigFlag(
            'infrangible_catalogproductprice/old_price/configurable_special_price'
        );

        if ($enabled && $priceBox->isProductList() && $priceBox->hasSpecialPrice()) {

            return $this->blockHelper->renderChildTemplateExtendedBlock(
                $priceBox,
                SpecialPrice::class,
                'Infrangible_CatalogProductPrice::configurable/special_price.phtml',
                ['price_box' => $priceBox]
            );
        }

        return '';
    }
}
