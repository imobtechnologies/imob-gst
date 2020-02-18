<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

/**
 * Tax Calculation Model
 */
class Calculation extends \Magento\Tax\Model\Calculation
{
    /**
     * Get calculation tax rate by specific request
     *
     * @param DataObject $request
     * @param null $productId
     * @return  float
     * @throws LocalizedException
     */
    public function getRate($request, $productId = null)
    {
        if (!$request->getCountryId() || !$request->getCustomerClassId() || !$request->getProductClassId()) {
            return 0;
        }
        $cacheKey = $this->_getRequestCacheKey($request, $productId);
        if (!isset($this->_rateCache[$cacheKey])) {
            $this->unsRateValue();
            $this->unsCalculationProcess();
            $this->unsEventModuleId();
            $this->_eventManager->dispatch('tax_rate_data_fetch', ['request' => $request, 'sender' => $this]);
            if (!$this->hasRateValue()) {
                $rateInfo = $this->_getResource()->getRateInfoCustom($request, $productId);

                $this->setCalculationProcess($rateInfo['process']);
                $this->setRateValue($rateInfo['value']);
            } else {
                $this->setCalculationProcess($this->_formCalculationProcess());
            }
            $this->_rateCache[$cacheKey] = $this->getRateValue();
            $this->_rateCalculationProcess[$cacheKey] = $this->getCalculationProcess();
        }
        return $this->_rateCache[$cacheKey];
    }

    /**
     * Get information about tax rates applied to request
     * Added product id as argument to bifurcate tax rate for each product
     *
     * @param DataObject $request
     * @param null $productId
     * @return  array
     * @throws LocalizedException
     */
    public function getAppliedRates($request, $productId = null)
    {
        if (!$request->getCountryId() || !$request->getCustomerClassId() || !$request->getProductClassId()) {
            return [];
        }

        $cacheKey = $this->_getRequestCacheKey($request, $productId);
        if (!isset($this->_rateCalculationProcess[$cacheKey])) {
            $this->_rateCalculationProcess[$cacheKey] = $this->_getResource()->getCalculationProcess($request);
        }
        return $this->_rateCalculationProcess[$cacheKey];
    }

    /**
     * Get cache key value for specific tax rate request
     * Updated to add product id in cache key to store different tax rates with different cache id for each item
     *
     * @param DataObject $request
     * @return  string
     */
    protected function _getRequestCacheKey($request, $productId = null)
    {
        $store = $request->getStore();
        $key = '';
        if ($store instanceof Store) {
            $key = $store->getId() . '|';
        } elseif (is_numeric($store)) {
            $key = $store . '|';
        }
        $key .= $request->getProductClassId() . '|'
            . $request->getCustomerClassId() . '|'
            . $request->getCountryId() . '|'
            . $request->getRegionId() . '|'
            . $request->getPostcode() . '|'
            . $productId;
        return $key;
    }
}
