<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Helper;

use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\SaleableInterface;

/**
 * Label, price and information text of the information line before the price.
 */
class Information extends Value
{
    /**
     * Returns the section of the price type in the configuration.
     *
     * @return string
     */
    protected function getSectionName(): string
    {
        return 'information';
    }

    /**
     * Returns the price of the information line in the currency of the store.
     *
     * @param SaleableInterface $saleable
     *
     * @return AmountInterface|null
     */
    public function getPrice(SaleableInterface $saleable): ?AmountInterface
    {
        $price = $this->getValue(
            $this->getSectionName(),
            'price',
            $saleable
        );

        return $price instanceof AmountInterface ? $price : $this->createAmount($price);
    }

    /**
     * Returns the label, amount and information text of the information line, if any.
     *
     * @param SaleableInterface $saleable
     *
     * @return array<string, string|float>
     */
    public function getData(SaleableInterface $saleable): array
    {
        $data = [];

        $label = $this->getLabel(
            $saleable,
            ''
        );

        if ($label !== '') {
            $data[ 'display_label' ] = $label;
        }

        $amount = $this->getPrice($saleable);

        if ($amount !== null) {
            $data[ 'amount' ] = $amount->getValue();
        }

        $information = $this->getInformation($saleable);

        if ($information !== '') {
            $data[ 'information' ] = $information;
        }

        return $data;
    }
}
