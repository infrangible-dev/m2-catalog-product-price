# Infrangible Catalog Product Price

Adds labels and information texts to the prices of products, for example "from", "RRP", "per metre" or "incl. VAT,
plus shipping", and an extra price line such as a recommended retail price. The texts come from product attributes, so
they can differ per product, with a default value for products without their own value.

## Prices

| Price type      | Where Magento shows it                                                           |
|-----------------|----------------------------------------------------------------------------------|
| **Final Price** | the price the customer pays                                                      |
| **Old Price**   | the regular price next to a special price                                        |
| **Min Price**   | the lowest price of products with a price range, for example bundle products     |
| **Max Price**   | the highest price of products with a price range                                 |
| **Information** | an extra line before the price: label, price and information text                |

Each price type has a **Label** and an **Information** text, the information line also a **Price**. Every value is read
from the product attribute selected for it; products without a value get the **Default Value**, which can also be used
without an attribute. Values of select and multiselect attributes are shown with their option texts.

- **Labels** are plain text. **Information texts** may contain the HTML tags `a`, `b`, `br`, `em`, `i`, `small`,
  `span`, `strong`, `sub`, `sup` and `u`, for example a link to the shipping costs; other tags are removed.
- **Price** of the information line: a price attribute (for example `msrp`) or a default value, in the base currency.
  It is shown in the currency of the store, without tax.
- The texts are shown on product pages and in product lists. For configurable products, labels, texts and the
  information line change with the selected variant (swatches and drop-downs).
- **Show Configurable Special Price** (Old Price): configurable products with a special price show the regular price
  as "Regular Price" in product lists.

## Configuration

Stores > Configuration > Infrangible Solutions > Catalog Product Price: the groups Final Price, Old Price, Min Price,
Max Price and Information each have an **Enable** switch and the sub-sections **Label** and **Information** (the group
Information also **Price**), each with **Enable**, **Attribute** and **Default Value**. All settings can be changed per
website and store view.

## Hyvä

[Infrangible Catalog Product Price Hyvä Theme](https://www.infrangible.dev/modules/catalog-product-price-hyva) shows
the texts in stores with the Hyvä theme. It uses `Helper\FinalPrice`, `Helper\OldPrice` and `Helper\Information`
(`getLabel()`, `getInformation()`, `getPrice()`).

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.1 or newer

## Installation

```bash
composer require infrangible/m2-catalog-product-price
bin/magento module:enable Infrangible_CatalogProductPrice
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy`.

## License

Infrangible Catalog Product Price is licensed under the MIT License - see the LICENSE file for details.
