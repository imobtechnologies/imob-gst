<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Caltype implements ArrayInterface
{

    const GLOBAL_TYPE = 0;
    const CATEGORY_TYPE = 1;
    const PRODUCT_TYPE = 2;

    public function toOptionArray()
    {
        return [
            ['value' => self::GLOBAL_TYPE, 'label' => __('Global')],
            ['value' => self::CATEGORY_TYPE, 'label' => __('Category')],
            ['value' => self::PRODUCT_TYPE, 'label' => __('Product (Requires Pro)')]
        ];
    }

    public function toArray()
    {
        return [
            self::GLOBAL_TYPE => __('Global'),
            self::CATEGORY_TYPE => __('Category'),
            self::PRODUCT_TYPE => __('Product (Requires Pro)')
        ];
    }
}
