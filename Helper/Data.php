<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const ENABLED = 'gst/general/enabled';
    const GSTIN = 'gst/general/gstin';
    const CIN = 'gst/general/cin';
    const PAN = 'gst/general/pan';
    const CAL_TYPE = 'gst/general/cal_type';
    const DEFAULT_GLOBAL = 'gst/general/default_global';
    const DEFAULT_CATEGORY = 'gst/general/default_category';
    const ORIGIN = 'gst/general/origin';
    const GSTIN_PDF = 'gst/general/gstin_pdf';

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(
            self::ENABLED,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getGSTIN()
    {
        return $this->scopeConfig->getValue(
            self::GSTIN,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getCIN()
    {
        return $this->scopeConfig->getValue(
            self::CIN,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getPAN()
    {
        return $this->scopeConfig->getValue(
            self::PAN,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getCalType()
    {
        return $this->scopeConfig->getValue(
            self::CAL_TYPE,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    public function getDefaultGlobalRate()
    {
        return $this->scopeConfig->getValue(
            self::DEFAULT_GLOBAL,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getDefaultCategoryRate()
    {
        return $this->scopeConfig->getValue(
            self::DEFAULT_CATEGORY,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getOrigin()
    {
        return $this->scopeConfig->getValue(
            self::ORIGIN,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function showGSTINOnPDF()
    {
        return $this->scopeConfig->getValue(
            self::GSTIN_PDF,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
