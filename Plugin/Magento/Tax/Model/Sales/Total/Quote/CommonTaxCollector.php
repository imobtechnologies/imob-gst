<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Plugin\Magento\Tax\Model\Sales\Total\Quote;

/**
 * Class CommonTaxCollector
 *
 * @package Imob\Gst\Plugin\Magento\Tax\Model\Sales\Total\Quote
 */
class CommonTaxCollector
{

    /**
     * Add product ID to itemDataObject
     *
     * @param \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject
     * @param $result
     * @param $itemDataObjectFactory
     * @param $item
     * @param $priceIncludesTax
     * @param $useBaseCurrency
     * @param null $parentCode
     * @return mixed
     */
    public function afterMapItem(
        \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject,
        $result,
        $itemDataObjectFactory,
        $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        $result->setTaxClassId($item->getProduct()->getId());
        return $result;
    }
}
