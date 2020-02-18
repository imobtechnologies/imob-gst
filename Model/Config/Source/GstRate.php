<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class GstRate extends AbstractSource
{

    const RATE_0 = 0;
    const RATE_5 = 5;
    const RATE_12 = 12;
    const RATE_18 = 18;
    const RATE_28 = 28;

    public function getAllOptions()
    {
        return [
            ['value' => "", 'label' => __('Select')],
            ['value' => self::RATE_0, 'label' => __('0%')],
            ['value' => self::RATE_5, 'label' => __('5%')],
            ['value' => self::RATE_12, 'label' => __('12%')],
            ['value' => self::RATE_18, 'label' => __('18%')],
            ['value' => self::RATE_28, 'label' => __('28%')]
        ];
    }
}
