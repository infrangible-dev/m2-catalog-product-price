# Changelog

## 2.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Older versions of Magento and PHP 7.1 to 8.1 are no longer
  supported; use version 1.x for these.
- Requires `infrangible/m2-core` 5.0, `infrangible/m2-foundation` 3.0 and `fewedev/base` 2.4.3.
- All classes have native types and use constructor property promotion. `Helper\Value` (and its subclasses) also takes
  the price currency and the escaper; `Helper\PriceBox` and `Helper\Amount` take other helpers than before.
- `Helper\Value::getLabel()` and `getInformation()` return a string instead of null.
- Information texts may only contain the HTML tags `a`, `b`, `br`, `em`, `i`, `small`, `span`, `strong`, `sub`, `sup`
  and `u`; other tags are removed.

### Fixed

- A value with a default value but no attribute selected (for example the label "RRP" of the information line) broke
  product pages and product lists with a TypeError. Now the default value is shown.
- A default value for the price of the information line broke product pages and product lists with a TypeError. Now it
  is shown as a price.
- The price of the information line was shown in the base currency with the symbol of the store currency (100 EUR as
  "100,00 $"), also after selecting a variant of a configurable product. Now it is converted.
- After selecting a variant of a configurable product, the HTML of information texts was shown as text (for example
  `<b>sale</b>`).
- Information texts were output without any filtering, so a product attribute value could run scripts in the shop.
- The settings of the groups Min Price, Max Price and Information were shown although the group was disabled; their
  dependency pointed to a group that does not exist.

### Added

- Translations in 24 locales.
- Unit tests.
