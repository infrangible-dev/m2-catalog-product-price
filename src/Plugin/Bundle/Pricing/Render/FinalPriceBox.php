<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Plugin\Bundle\Pricing\Render;

use Infrangible\CatalogProductPrice\Helper\PriceBox;
use Magento\Bundle\Pricing\Render\FinalPriceBox as Subject;
use Magento\Framework\Pricing\Amount\AmountInterface;

/**
 * Replaces the plugin of the framework price box (same plugin name) for bundle products.
 */
class FinalPriceBox
{
    /**
     * @param PriceBox $priceBoxHelper
     */
    public function __construct(protected PriceBox $priceBoxHelper)
    {
    }

    /**
     * Adds the information line before the price box.
     *
     * @param Subject $subject
     * @param string $result
     *
     * @return string
     */
    public function afterFetchView(Subject $subject, string $result): string
    {
        return $this->priceBoxHelper->getInformationHtml($subject) . $result;
    }

    /**
     * Replaces the label of the rendered amount with the label of its price type.
     *
     * @param Subject $subject
     * @param AmountInterface $amount
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     *
     * @return array{0: AmountInterface, 1: array<string, mixed>}
     */
    public function beforeRenderAmount(Subject $subject, AmountInterface $amount, array $arguments = []): array
    {
        return [$amount, $this->priceBoxHelper->updateDisplayLabel($subject, $arguments)];
    }
}
