<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Test\Unit\Helper;

use Infrangible\CatalogProductPrice\Helper\Amount;
use Infrangible\CatalogProductPrice\Helper\FinalPrice;
use Infrangible\CatalogProductPrice\Helper\Information;
use Infrangible\CatalogProductPrice\Helper\MaxPrice;
use Infrangible\CatalogProductPrice\Helper\MinPrice;
use Infrangible\CatalogProductPrice\Helper\OldPrice;
use Infrangible\CatalogProductPrice\Helper\PriceBox;
use Infrangible\Core\Helper\Block;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Pricing\Render\PriceBox as PriceBoxRender;
use Magento\Framework\Pricing\SaleableInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the labels of the price types and the information line of price boxes.
 */
#[AllowMockObjectsWithoutExpectations]
class PriceBoxTest extends TestCase
{
    /**
     * Returns price types and the label that each gets.
     *
     * @return array<string, array{string, string}>
     */
    public static function priceTypeProvider(): array
    {
        return [
            'final price' => ['finalPrice', 'final'],
            'old price'   => ['oldPrice', 'old'],
            'min price'   => ['minPrice', 'min'],
            'max price'   => ['maxPrice', 'max'],
            'other'       => ['basePrice', 'Original']
        ];
    }

    /**
     * Tests that each price type gets the label of its helper.
     *
     * @dataProvider priceTypeProvider
     *
     * @param string $priceType
     * @param string $expectedLabel
     *
     * @return void
     */
    #[DataProvider('priceTypeProvider')]
    public function testUpdateDisplayLabel(string $priceType, string $expectedLabel): void
    {
        $arguments = $this->createHelper($this->createStub(Information::class))->updateDisplayLabel(
            $this->createPriceBox([]),
            ['price_type' => $priceType, 'display_label' => 'Original', 'price_id' => 'x']
        );

        self::assertSame(
            ['price_type' => $priceType, 'display_label' => $expectedLabel, 'price_id' => 'x'],
            $arguments
        );
    }

    /**
     * Tests that the information line is rendered for the final price and in product lists only, and not without data.
     *
     * @return void
     */
    public function testInformationHtml(): void
    {
        $information = $this->createStub(Information::class);
        $information->method('getData')->willReturn(['display_label' => 'RRP']);

        $blockHelper = $this->createMock(Block::class);
        $blockHelper->expects($this->exactly(2))->method('renderChildTemplateExtendedBlock')->with(
            $this->anything(),
            $this->anything(),
            'Infrangible_CatalogProductPrice::information.phtml',
            ['display_label' => 'RRP', 'include_container' => true]
        )->willReturn('<span>RRP</span>');

        $helper = $this->createHelper($information, $blockHelper);

        $finalPrice = $this->createPriceBox(['price_type_code' => 'final_price']);
        $productList = $this->createPriceBox(['zone' => 'item_list']);

        self::assertSame('<span>RRP</span>', $helper->getInformationHtml($finalPrice));
        self::assertSame('<span>RRP</span>', $helper->getInformationHtml($productList));
        self::assertSame('', $helper->getInformationHtml($this->createPriceBox(['price_type_code' => 'tier_price'])));

        $emptyInformation = $this->createStub(Information::class);
        $emptyInformation->method('getData')->willReturn([]);

        self::assertSame(
            '',
            $this->createHelper($emptyInformation)->getInformationHtml(
                $this->createPriceBox(['price_type_code' => 'final_price'])
            )
        );
    }

    /**
     * Returns the helper with value helpers that return the name of their price type as label.
     *
     * @param Information $information
     * @param Block|null  $blockHelper
     *
     * @return PriceBox
     */
    private function createHelper(Information $information, ?Block $blockHelper = null): PriceBox
    {
        $finalPrice = $this->createStub(FinalPrice::class);
        $finalPrice->method('getLabel')->willReturn('final');

        $oldPrice = $this->createStub(OldPrice::class);
        $oldPrice->method('getLabel')->willReturn('old');

        $minPrice = $this->createStub(MinPrice::class);
        $minPrice->method('getLabel')->willReturn('min');

        $maxPrice = $this->createStub(MaxPrice::class);
        $maxPrice->method('getLabel')->willReturn('max');

        $amountHelper = new Amount($finalPrice, $oldPrice, $minPrice, $maxPrice);

        return new PriceBox(
            $amountHelper,
            $information,
            $blockHelper ?? $this->createStub(Block::class),
            $this->createStub(Stores::class)
        );
    }

    /**
     * Returns a price box with the data.
     *
     * @param array $data
     * @phpstan-param array<string, string> $data
     *
     * @return PriceBoxRender
     */
    private function createPriceBox(array $data): PriceBoxRender
    {
        $priceBox = $this->createStub(PriceBoxRender::class);
        $priceBox->method('getData')->willReturnCallback(
            static fn(string $key = ''): ?string => $data[ $key ] ?? null
        );
        $priceBox->method('getSaleableItem')->willReturn($this->createStub(SaleableInterface::class));

        return $priceBox;
    }
}
