<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Plugin\Framework\Pricing\Render;

use Infrangible\CatalogProductPrice\Helper\Amount as AmountHelper;
use Infrangible\Core\Helper\Block;
use Magento\Framework\Pricing\Render\Amount as Subject;

/**
 * Adds the information text of the price type to rendered amounts.
 */
class Amount
{
    /**
     * @param AmountHelper $amountHelper
     * @param Block $blockHelper
     */
    public function __construct(protected AmountHelper $amountHelper, protected Block $blockHelper)
    {
    }

    /**
     * Marks the amount, so the CSS shows old prices with a label also when Magento hides them.
     *
     * @param Subject $subject
     *
     * @return void
     */
    public function beforeToHtml(Subject $subject): void
    {
        $cssClasses = $subject->getData('css_classes');

        $cssClasses = is_string($cssClasses) && $cssClasses !== '' ? explode(' ', $cssClasses) : [];

        if (! in_array('force-display', $cssClasses, true)) {
            $cssClasses[] = 'force-display';
        }

        $subject->setData('css_classes', implode(' ', $cssClasses));
    }

    /**
     * Reports adjustments when the price type has an information text.
     *
     * @param Subject $subject
     * @param bool $result
     *
     * @return bool
     */
    public function afterHasAdjustmentsHtml(Subject $subject, bool $result): bool
    {
        return $result || $this->amountHelper->getInformation($subject) !== '';
    }

    /**
     * Adds the information text of the price type after the adjustments.
     *
     * @param Subject $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetAdjustmentsHtml(Subject $subject, string $result): string
    {
        $information = $this->amountHelper->getInformation($subject);

        if ($information === '') {
            return $result;
        }

        $html = $this->blockHelper->renderChildTemplateBlock(
            $subject,
            'Infrangible_CatalogProductPrice::price/information.phtml',
            ['information' => $information]
        );

        return $result . $html;
    }
}
