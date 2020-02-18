<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

/**
 * Tax Calculation Resource Model
 */

namespace Imob\Gst\Model\ResourceModel;

use Imob\Gst\Model\Config\Source\Caltype;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;

class Calculation extends \Magento\Tax\Model\ResourceModel\Calculation
{

    public function __construct(
        Context $context,
        Data $taxData,
        StoreManagerInterface $storeManager,
        \Imob\Gst\Helper\Data $helper,
        CategoryRepository $categoryRepository,
        ProductFactory $productRepository,
        $connectionName = null
    )
    {
        parent::__construct($context, $taxData, $storeManager, $connectionName);
        $this->helper = $helper;
        $this->categoryRepository = $categoryRepository;
        $this->_productRepository = $productRepository;
    }

    /**
     * Get tax rate information: calculation process data and tax rate
     * Updated default getRate function to pass productId argument
     *
     * @param $request
     * @param $productId
     * @return array
     */
    public function getRateInfoCustom($request, $productId)
    {
        /**
         * If Country is India then use GST rates, else use default magento rates
         */
        if ($this->helper->isEnabled() && $request->getCountryId() == 'IN') {
            $rates = $this->getGstRates($request, $productId);
        } else {
            $rates = $this->_getRates($request);
        }
        return [
            'process' => $this->getCalculationProcess($request, $rates),
            'value' => $this->_calculateRate($rates)
        ];
    }

    /**
     * Get GST rates based on configuration and product data
     *
     * @param $request
     * @param null $productId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getGstRates($request, $productId = null)
    {
        $storeId = $this->_storeManager->getStore($request->getStore())->getId();
        $customerClassId = $request->getCustomerClassId();
        $countryId = $request->getCountryId();
        $regionId = $request->getRegionId();
        $postcode = $request->getPostcode();
        $rates = [];

        // Process productClassId as it can be array or usual value. Form best key for cache.
        $productClassId = $request->getProductClassId();
        $ids = is_array($productClassId) ? $productClassId : [$productClassId];
        foreach ($ids as $key => $val) {
            $ids[$key] = (int)$val; // Make it integer for equal cache keys even in case of null/false/0 values
        }
        $ids = array_unique($ids);
        sort($ids);
        $productClassKey = implode(',', $ids);

        // Form cache key and either get data from cache or from DB
        $cacheKey = implode(
            '|',
            [$storeId, $customerClassId, $productClassKey, $countryId, $regionId, $postcode, $productId]
        );

        $rate = 0;
        if (!isset($this->_ratesCache[$cacheKey]) && $this->helper->isEnabled() && $countryId == 'IN') {
            /**
             * If calculation type is global, use global GST rate for all items in cart
             */
            if ($this->helper->getCalType() == Caltype::GLOBAL_TYPE) {
                $rate = $this->helper->getDefaultGlobalRate();
            } elseif ($productId && $this->helper->getCalType() == Caltype::CATEGORY_TYPE) {
                /**
                 * If calculation type is category, load product using id and get its category data
                 * Note that we are using the first category item for a product, so if a product has multiple
                 * categories, only first category in array will be used to get GST rates
                 */
                try {
                    $product = $this->getProductById($productId);
                    $categoryIds = $product->getCategoryIds();
                    $categoryId = $categoryIds[0];

                    $category = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
                    $rate = $category->getCatGstRate();
                    if ($rate == "" || $rate == null) {
                        $rate = $this->helper->getDefaultCategoryRate();
                    }
                } catch (Exception $e) {
                }
            }
            if ($rate != 0) {
                /**
                 * If shipping region/state is same as business origin region/state
                 * two GST will be used SGST and CGST
                 */
                if ($this->helper->getOrigin() == $regionId) {
                    $rates[0] = $this->getGstRateArray('SGST', $rate / 2, $countryId, $regionId);
                    $rates[1] = $this->getGstRateArray('CGST', $rate / 2, $countryId, $regionId);
                } else {
                    /**
                     * Else single GST will be used : IGST
                     */
                    $rates[0] = $this->getGstRateArray('IGST', $rate, $countryId, $regionId);
                }
            }
            $this->_ratesCache[$cacheKey] = $rates;
        }

        return $this->_ratesCache[$cacheKey];
    }

    /**
     * Load product by id
     *
     * @param $id
     * @return Product
     */
    public function getProductById($id)
    {
        return $this->_productRepository->create()->load($id);
    }

    /**
     * The function returns a Tax rate array with GST data
     *
     * @param $code
     * @param $rate
     * @param $countryId
     * @param $regionId
     * @return array
     */
    protected function getGstRateArray($code, $rate, $countryId, $regionId)
    {
        return [
            "tax_calculation_rate_id" => $code . "-" . $rate,
            "tax_calculation_rule_id" => $code . "-" . $rate,
            "customer_tax_class_id" => $code . "-" . $rate,
            "product_tax_class_id" => $code . "-" . $rate,
            "priority" => "0",
            "position" => "0",
            "calculate_subtotal" => "0",
            "value" => $rate,
            "tax_country_id" => $countryId,
            "tax_region_id" => $regionId,
            "tax_postcode" => "*",
            "code" => $code . "-" . $rate,
            "title" => $code
        ];
    }
}
