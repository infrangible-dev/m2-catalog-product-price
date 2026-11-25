<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\SaleableInterface;

/**
 * Reads the label, information text or price of a price type from the configured product attribute.
 */
abstract class Value extends AbstractHelper
{
    /** HTML tags that information texts may contain, for example a link to the shipping costs */
    public const ALLOWED_INFORMATION_TAGS = ['a', 'b', 'br', 'em', 'i', 'small', 'span', 'strong', 'sub', 'sup', 'u'];

    /**
     * @param Context $context
     * @param Variables $variables
     * @param Stores $storeHelper
     * @param Attribute $attributeHelper
     * @param AmountFactory $amountFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        protected Variables $variables,
        protected Stores $storeHelper,
        protected Attribute $attributeHelper,
        protected AmountFactory $amountFactory,
        protected PriceCurrencyInterface $priceCurrency,
        protected Escaper $escaper
    ) {
        parent::__construct($context);
    }

    /**
     * Returns the section of the price type in the configuration.
     *
     * @return string
     */
    abstract protected function getSectionName(): string;

    /**
     * Checks whether the section of a price type is enabled.
     *
     * @param string $sectionName
     *
     * @return bool
     */
    public function isSectionEnabled(string $sectionName): bool
    {
        return $this->storeHelper->getStoreConfigFlag(
            sprintf(
                'infrangible_catalogproductprice/%s/enable',
                $sectionName
            )
        );
    }

    /**
     * Returns the label as plain text: templates escape it.
     *
     * @param SaleableInterface $saleableItem
     * @param Phrase|string $defaultValue
     *
     * @return string
     */
    public function getLabel(SaleableInterface $saleableItem, Phrase|string $defaultValue): string
    {
        return $this->variables->stringValueOrDefault(
            $this->getValue(
                $this->getSectionName(),
                'label',
                $saleableItem,
                $defaultValue
            )
        );
    }

    /**
     * Returns the information text as HTML with the tags of ALLOWED_INFORMATION_TAGS only.
     *
     * @param SaleableInterface $saleableItem
     *
     * @return string
     */
    public function getInformation(SaleableInterface $saleableItem): string
    {
        $information = $this->variables->stringValueOrDefault(
            $this->getValue(
                $this->getSectionName(),
                'information',
                $saleableItem,
                ''
            )
        );

        return $information === '' ? '' : $this->escaper->escapeHtml(
            $information,
            self::ALLOWED_INFORMATION_TAGS
        );
    }

    /**
     * Checks whether a value (label, information, price) of a section is enabled.
     *
     * @param string $sectionName
     * @param string $type
     *
     * @return bool
     */
    public function isValueEnabled(string $sectionName, string $type): bool
    {
        return $this->storeHelper->getStoreConfigFlag(
            sprintf(
                'infrangible_catalogproductprice/%s/%s/enable',
                $sectionName,
                $type
            )
        );
    }

    /**
     * Returns the value of the configured product attribute, or the configured default value.
     *
     * Values of price attributes are returned as amount in the currency of the store.
     *
     * @param string $sectionName
     * @param string $type
     * @param SaleableInterface $saleableItem
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getValue(
        string $sectionName,
        string $type,
        SaleableInterface $saleableItem,
        mixed $defaultValue = null
    ): mixed {
        if (! $this->isSectionEnabled($sectionName) || ! $this->isValueEnabled($sectionName, $type)) {
            return $defaultValue;
        }

        if (! ($saleableItem instanceof Product)) {
            return $defaultValue;
        }

        $path = sprintf(
            'infrangible_catalogproductprice/%s/%s/',
            $sectionName,
            $type
        );

        $attribute = $this->getConfiguredAttribute($path . 'attribute_id');

        $productValue = null;

        if ($attribute !== null) {
            try {
                $productValue = $this->getAttributeValue(
                    $attribute,
                    $saleableItem->getData($attribute->getAttributeCode())
                );
            } catch (LocalizedException) {
                return $defaultValue;
            }
        }

        if ($this->variables->isEmpty($productValue)) {
            $productValue = $this->storeHelper->getStoreConfig($path . 'default_value');

            if ($attribute !== null && ! $this->variables->isEmpty($productValue)
                && $attribute->getFrontendInput() === 'price') {

                $productValue = $this->createAmount($productValue);
            }
        }

        return $this->variables->isEmpty($productValue) ? $defaultValue : $productValue;
    }

    /**
     * Returns the product attribute selected in the configuration path, or null.
     *
     * @param string $path
     *
     * @return EavAttribute|null
     */
    private function getConfiguredAttribute(string $path): ?EavAttribute
    {
        $attributeId = $this->variables->stringValueOrDefault($this->storeHelper->getStoreConfig($path));

        if ($attributeId === '') {
            return null;
        }

        try {
            return $this->attributeHelper->getAttribute(
                Product::ENTITY,
                $attributeId
            );
        } catch (LocalizedException) {
            return null;
        }
    }

    /**
     * Returns the option text of select attributes, an amount for price attributes or the value itself.
     *
     * @param EavAttribute $attribute
     * @param mixed $value
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function getAttributeValue(EavAttribute $attribute, mixed $value): mixed
    {
        if ($this->variables->isEmpty($value)) {
            return null;
        }

        if ($attribute->getFrontendInput() !== 'boolean' && $attribute->usesSource()) {
            $optionTexts = [];

            // multiselect attributes store the option IDs separated by commas
            foreach (explode(',', $this->variables->stringValueOrDefault($value)) as $optionId) {
                $optionText = $attribute->getSource()->getOptionText(trim($optionId));

                if (is_string($optionText) && $optionText !== '') {
                    $optionTexts[] = $optionText;
                }
            }

            return implode(', ', $optionTexts);
        }

        if ($attribute->getFrontendInput() === 'price') {
            return $this->createAmount($value);
        }

        return $value;
    }

    /**
     * Creates an amount in the currency of the store from a price in the base currency.
     *
     * @param mixed $value
     *
     * @return AmountInterface|null
     */
    protected function createAmount(mixed $value): ?AmountInterface
    {
        if (! is_numeric($value)) {
            return null;
        }

        return $this->amountFactory->create(
            $this->priceCurrency->convertAndRound((float)$value)
        );
    }
}
