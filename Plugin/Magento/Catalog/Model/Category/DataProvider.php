<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Plugin\Magento\Catalog\Model\Category;

use Imob\Gst\Helper\Data;
use Imob\Gst\Model\Config\Source\Caltype;
use Magento\Framework\Exception\NoSuchEntityException;

class DataProvider
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * DataProvider constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * A function to disable category GST rate configuration if calculation type is not Category based
     *
     * @param \Magento\Catalog\Model\Category\DataProvider $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetData(
        \Magento\Catalog\Model\Category\DataProvider $subject,
        $result
    ) {
        $categoryId = $subject->getCurrentCategory()->getId();
        if ($this->helper->getCalType() == Caltype::CATEGORY_TYPE) :
            $result[$categoryId]['hide_gst_configuration'] = false;
        else :
            $result[$categoryId]['hide_gst_configuration'] = true;
        endif;
        return $result;
    }
}
