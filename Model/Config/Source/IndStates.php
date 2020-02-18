<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Model\Config\Source;

use Magento\Directory\Model\Country;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\Element\Template\Context;

class IndStates implements ArrayInterface
{
    const IND_COUNTRY_CODE = 'IN';
    /**
     * IndStates constructor.
     * @param Context $context
     * @param Country $country
     * @param array $data
     */
    public function __construct(
        Context $context,
        Country $country,
        array $data = []
    ) {
        $this->country = $country;
    }

    public function toOptionArray()
    {
        $regionCollection = $this->country->loadByCode(self::IND_COUNTRY_CODE)->getRegions();
        return $regionCollection->loadData()->toOptionArray(false);
    }
}
