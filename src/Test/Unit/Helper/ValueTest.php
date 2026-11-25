<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Test\Unit\Helper;

use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPrice\Helper\FinalPrice;
use Infrangible\CatalogProductPrice\Helper\Information;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\ZendEscaper;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\SaleableInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests how labels, information texts and prices are read from the configured product attributes.
 */
#[AllowMockObjectsWithoutExpectations]
class ValueTest extends TestCase
{
    /** @var array<string, string> */
    private array $config = [];

    /** @var array<string, EavAttribute> */
    private array $attributes = [];

    /**
     * Sets up the object manager, which Magento's escaper uses for the Laminas escaper.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback($this->createObject(...));
        ObjectManager::setInstance($objectManager);

        $this->config = [];
        $this->attributes = [];
    }

    /**
     * Tests that a disabled section returns the default value.
     *
     * @return void
     */
    public function testDisabledSection(): void
    {
        $this->configure('final_price', 'label', '10', 'Default', false);
        $this->addAttribute('10', 'price_label', 'text');

        $product = $this->createProduct(['price_label' => 'Now']);

        self::assertSame('From', $this->createFinalPrice()->getLabel($product, 'From'));
    }

    /**
     * Tests the label from a text attribute and the default value for products without a value.
     *
     * @return void
     */
    public function testLabelFromAttribute(): void
    {
        $this->configure('final_price', 'label', '10', 'Default');
        $this->addAttribute('10', 'price_label', 'text');

        $helper = $this->createFinalPrice();

        self::assertSame('Now & then', $helper->getLabel($this->createProduct(['price_label' => 'Now & then']), ''));
        self::assertSame('Default', $helper->getLabel($this->createProduct([]), ''));
    }

    /**
     * Tests that the default value is used when no attribute is selected.
     *
     * @return void
     */
    public function testDefaultValueWithoutAttribute(): void
    {
        $this->configure('final_price', 'label', '', 'RRP');

        self::assertSame('RRP', $this->createFinalPrice()->getLabel($this->createProduct([]), ''));
    }

    /**
     * Tests that a product that is not a catalog product gets the default value of the caller.
     *
     * @return void
     */
    public function testOtherSaleable(): void
    {
        $this->configure('final_price', 'label', '10', 'Default');

        $saleable = $this->createStub(SaleableInterface::class);

        self::assertSame('From', $this->createFinalPrice()->getLabel($saleable, 'From'));
    }

    /**
     * Tests the option texts of select and multiselect attributes.
     *
     * @return void
     */
    public function testOptionTexts(): void
    {
        $this->configure('final_price', 'label', '11', '');
        $this->addAttribute(
            '11',
            'price_unit',
            'multiselect',
            ['1' => 'per metre', '2' => 'per roll']
        );

        $helper = $this->createFinalPrice();

        self::assertSame('per metre', $helper->getLabel($this->createProduct(['price_unit' => '1']), ''));
        self::assertSame('per metre, per roll', $helper->getLabel($this->createProduct(['price_unit' => '1,2']), ''));
    }

    /**
     * Tests that information texts keep the allowed tags only.
     *
     * @return void
     */
    public function testInformationAllowsSomeTags(): void
    {
        $this->configure('final_price', 'information', '12', '');
        $this->addAttribute('12', 'price_information', 'textarea');

        $information = $this->createFinalPrice()->getInformation(
            $this->createProduct(
                ['price_information' => 'incl. VAT, plus <a href="/shipping">shipping</a><script>alert(1)</script>']
            )
        );

        self::assertStringContainsString('<a href="/shipping">shipping</a>', $information);
        self::assertStringNotContainsString('<script', $information);
    }

    /**
     * Tests that prices of price attributes are converted into the currency of the store.
     *
     * @return void
     */
    public function testPriceIsConverted(): void
    {
        $this->configure('information', 'price', '13', '');
        $this->addAttribute('13', 'msrp', 'price');

        $price = $this->createInformation()->getPrice($this->createProduct(['msrp' => '100.0000']));

        self::assertNotNull($price);
        self::assertSame(150.0, $price->getValue());
    }

    /**
     * Tests that the default value of a price is a converted amount as well.
     *
     * @return void
     */
    public function testDefaultPriceIsAmount(): void
    {
        $this->configure('information', 'price', '13', '20');
        $this->addAttribute('13', 'msrp', 'price');

        $price = $this->createInformation()->getPrice($this->createProduct([]));

        self::assertNotNull($price);
        self::assertSame(30.0, $price->getValue());
    }

    /**
     * Tests the data of the information line.
     *
     * @return void
     */
    public function testInformationData(): void
    {
        $this->configure('information', 'label', '', 'RRP');
        $this->configure('information', 'price', '13', '');
        $this->configure('information', 'information', '', 'incl. VAT');
        $this->addAttribute('13', 'msrp', 'price');

        self::assertSame(
            ['display_label' => 'RRP', 'amount' => 15.0, 'information' => 'incl. VAT'],
            $this->createInformation()->getData($this->createProduct(['msrp' => '10']))
        );
        self::assertSame(
            ['display_label' => 'RRP', 'information' => 'incl. VAT'],
            $this->createInformation()->getData($this->createProduct([]))
        );
    }

    /**
     * Enables a value of a section and sets its attribute and default value.
     *
     * @param string $section
     * @param string $type
     * @param string $attributeId
     * @param string $defaultValue
     * @param bool   $enabled
     *
     * @return void
     */
    private function configure(
        string $section,
        string $type,
        string $attributeId,
        string $defaultValue,
        bool $enabled = true
    ): void {
        $prefix = 'infrangible_catalogproductprice/' . $section . '/';

        $this->config[ $prefix . 'enable' ] = $enabled ? '1' : '0';
        $this->config[ $prefix . $type . '/enable' ] = '1';
        $this->config[ $prefix . $type . '/attribute_id' ] = $attributeId;
        $this->config[ $prefix . $type . '/default_value' ] = $defaultValue;
    }

    /**
     * Adds a product attribute that the attribute helper returns for the ID.
     *
     * @param string                              $attributeId
     * @param string                              $code
     * @param string                              $frontendInput
     * @param array $options
     * @phpstan-param array<int|string, string> $options
     *
     * @return void
     */
    private function addAttribute(string $attributeId, string $code, string $frontendInput, array $options = []): void
    {
        $attribute = $this->createStub(EavAttribute::class);
        $attribute->method('getAttributeCode')->willReturn($code);
        $attribute->method('getFrontendInput')->willReturn($frontendInput);
        $attribute->method('usesSource')->willReturn($options !== []);

        $source = $this->createStub(AbstractSource::class);
        $source->method('getOptionText')->willReturnCallback(
            static fn(mixed $value): string|bool => is_string($value) || is_int($value) ?
                $options[ $value ] ?? false : false
        );
        $attribute->method('getSource')->willReturn($source);

        $this->attributes[ $attributeId ] = $attribute;
    }

    /**
     * Returns a product with the data.
     *
     * @param array $data
     * @phpstan-param array<string, string> $data
     *
     * @return Product
     */
    private function createProduct(array $data): Product
    {
        $product = $this->createPartialMock(Product::class, []);
        $product->setData($data);

        return $product;
    }

    /**
     * Returns the helper of the final price.
     *
     * @return FinalPrice
     */
    private function createFinalPrice(): FinalPrice
    {
        return new FinalPrice(...$this->createArguments());
    }

    /**
     * Returns the helper of the information line.
     *
     * @return Information
     */
    private function createInformation(): Information
    {
        return new Information(...$this->createArguments());
    }

    /**
     * Returns the constructor arguments of the helpers: the configuration of this test, its attributes and a currency
     * rate of 1.5.
     *
     * @return array{Context, Variables, Stores, Attribute, AmountFactory, PriceCurrencyInterface, Escaper}
     */
    private function createArguments(): array
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfig')->willReturnCallback(
            fn(string $path): ?string => $this->config[ $path ] ?? null
        );
        $storeHelper->method('getStoreConfigFlag')->willReturnCallback(
            fn(string $path): bool => ($this->config[ $path ] ?? '') === '1'
        );

        $attributeHelper = $this->createStub(Attribute::class);
        $attributeHelper->method('getAttribute')->willReturnCallback(
            fn(string $entityTypeCode, string $attributeId): EavAttribute => $this->attributes[ $attributeId ] ??
                throw new NoSuchEntityException()
        );

        $amountFactory = $this->createStub(AmountFactory::class);
        $amountFactory->method('create')->willReturnCallback($this->createAmount(...));

        $priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $priceCurrency->method('convertAndRound')->willReturnCallback(
            static fn(float $amount): float => round($amount * 1.5, 2)
        );

        return [
            $this->createStub(Context::class),
            new Variables(),
            $storeHelper,
            $attributeHelper,
            $amountFactory,
            $priceCurrency,
            new Escaper()
        ];
    }

    /**
     * Returns an amount with the value.
     *
     * @param float $value
     *
     * @return AmountInterface
     */
    private function createAmount(float $value): AmountInterface
    {
        $amount = $this->createStub(AmountInterface::class);
        $amount->method('getValue')->willReturn($value);

        return $amount;
    }

    /**
     * Returns the real Laminas escaper, and a stub of other classes, as the object manager does.
     *
     * @param string $type
     * @phpstan-param class-string $type
     *
     * @return object
     */
    private function createObject(string $type): object
    {
        return $type === ZendEscaper::class ? new ZendEscaper() : $this->createStub($type);
    }
}
