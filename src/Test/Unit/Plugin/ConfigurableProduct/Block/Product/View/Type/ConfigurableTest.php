<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Test\Unit\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPrice\Helper\FinalPrice;
use Infrangible\CatalogProductPrice\Helper\Information;
use Infrangible\CatalogProductPrice\Helper\OldPrice;
use Infrangible\CatalogProductPrice\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\SaleableInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the labels, information texts and information prices in the JSON configuration of configurable products.
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurableTest extends TestCase
{
    /**
     * Tests the values of the product and of its variants.
     *
     * @return void
     */
    public function testAfterGetJsonConfig(): void
    {
        $config = [
            'prices'       => ['finalPrice' => ['amount' => 50], 'oldPrice' => ['amount' => 60]],
            'optionPrices' => [
                '2' => ['finalPrice' => ['amount' => 40], 'oldPrice' => ['amount' => 60]],
                '3' => ['finalPrice' => ['amount' => 45], 'oldPrice' => ['amount' => 45]]
            ]
        ];

        $result = (new Json())->decode($this->createPlugin()->afterGetJsonConfig(
            $this->createSubject(),
            (string)(new Json())->encode($config)
        ));

        self::assertSame(
            [
                'prices'       => [
                    'finalPrice'       => ['amount' => 50, 'displayLabel' => 'from'],
                    'oldPrice'         => ['amount' => 60],
                    'priceInformation' => ['displayLabel' => 'RRP', 'price' => 70.5]
                ],
                'optionPrices' => [
                    2 => [
                        'finalPrice'       => ['amount' => 40, 'displayLabel' => 'now', 'information' => '<b>sale</b>'],
                        'oldPrice'         => ['amount' => 60, 'displayLabel' => 'before'],
                        'priceInformation' => ['displayLabel' => 'RRP', 'price' => 70.5]
                    ],
                    3 => ['finalPrice' => ['amount' => 45], 'oldPrice' => ['amount' => 45]]
                ]
            ],
            $result
        );
    }

    /**
     * Tests that a configuration without the keys of the prices stays as it is.
     *
     * @return void
     */
    public function testConfigWithoutPrices(): void
    {
        $subject = $this->createStub(Subject::class);
        $subject->method('getProduct')->willReturn($this->createProduct(3));
        $subject->method('getAllowProducts')->willReturn([]);

        self::assertSame('{"attributes":[]}', $this->createPlugin()->afterGetJsonConfig($subject, '{"attributes":[]}'));
    }

    /**
     * Returns the plugin with helpers whose values depend on the product ID: product 1 is the configurable product,
     * 2 and 3 are its variants. Product 3 has no values.
     *
     * @return Configurable
     */
    private function createPlugin(): Configurable
    {
        $finalPrice = $this->createStub(FinalPrice::class);
        $finalPrice->method('getLabel')->willReturnCallback(
            fn(SaleableInterface $product): string => [1 => 'from', 2 => 'now'][ $this->getProductId($product) ] ?? ''
        );
        $finalPrice->method('getInformation')->willReturnCallback(
            fn(SaleableInterface $product): string => $this->getProductId($product) === 2 ? '<b>sale</b>' : ''
        );

        $oldPrice = $this->createStub(OldPrice::class);
        $oldPrice->method('getLabel')->willReturnCallback(
            fn(SaleableInterface $product): string => $this->getProductId($product) === 2 ? 'before' : ''
        );
        $oldPrice->method('getInformation')->willReturn('');

        $amount = $this->createStub(AmountInterface::class);
        $amount->method('getValue')->willReturn(70.5);

        $information = $this->createStub(Information::class);
        $information->method('getLabel')->willReturnCallback(
            fn(SaleableInterface $product): string => $this->getProductId($product) === 3 ? '' : 'RRP'
        );
        $information->method('getInformation')->willReturn('');
        $information->method('getPrice')->willReturnCallback(
            fn(SaleableInterface $product): ?AmountInterface => $this->getProductId($product) === 3 ? null : $amount
        );

        return new Configurable($finalPrice, $information, new Json(), $oldPrice, new Variables());
    }

    /**
     * Returns the block of the configurable product 1 with the variants 2 and 3.
     *
     * @return Subject
     */
    private function createSubject(): Subject
    {
        $subject = $this->createStub(Subject::class);
        $subject->method('getProduct')->willReturn($this->createProduct(1));
        $subject->method('getAllowProducts')->willReturn([$this->createProduct(2), $this->createProduct(3)]);

        return $subject;
    }

    /**
     * Returns a product with the ID.
     *
     * @param int $productId
     *
     * @return Product
     */
    private function createProduct(int $productId): Product
    {
        $product = $this->createPartialMock(Product::class, []);
        $product->setId($productId);

        return $product;
    }

    /**
     * Returns the ID of a product of this test.
     *
     * @param SaleableInterface $product
     *
     * @return int
     */
    private function getProductId(SaleableInterface $product): int
    {
        return $product instanceof Product ? (int)$product->getId() : 0;
    }
}
