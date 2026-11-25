<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPrice\Helper;

/**
 * Label and information text of the old (regular) price.
 */
class OldPrice extends Value
{
    /**
     * Returns the section of the price type in the configuration.
     *
     * @return string
     */
    protected function getSectionName(): string
    {
        return 'old_price';
    }
}
