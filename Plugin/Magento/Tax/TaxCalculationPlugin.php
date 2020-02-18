<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Plugin\Magento\Tax;

use Imob\Gst\Helper\Data;
use Imob\Gst\Model\Calculator;
use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\TaxCalculationInterface;

/**
 * Handle tax calculation through Vertex
 */
class TaxCalculationPlugin
{
    /** @var Calculator */
    private $calculator;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Calculator $calculator
     * @param Data $helper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Calculator $calculator,
        Data $helper
    ) {
        $this->storeManager = $storeManager;
        $this->calculator = $calculator;
        $this->helper = $helper;
    }

    /**
     * Use Vertex to calculate tax if it can be used
     *
     * @param TaxCalculationInterface $subject
     * @param callable $super
     * @param QuoteDetailsInterface $quoteDetails
     * @param string|null $storeId
     * @param bool $round
     * @return TaxDetailsInterface
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     * @see TaxCalculationInterface::calculateTax()
     */
    public function aroundCalculateTax(
        TaxCalculationInterface $subject,
        callable $super,
        QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true
    ) {
        $storeId = $this->getStoreId($storeId);
        /**
         * Use GST calculator if module is enabled
         */
        if (!$this->helper->isEnabled()) {
            // Allows forward compatibility with argument additions
            $arguments = func_get_args();
            array_splice($arguments, 0, 2);
            return call_user_func_array($super, $arguments);
        }

        return $this->calculator->calculateTax($quoteDetails, $storeId, $round);
    }

    /**
     * Retrieve current Store ID
     *
     * @param string|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreId($storeId)
    {
        return $storeId ?: $this->storeManager->getStore()->getStoreId();
    }

    /**
     * Determine whether a quote is virtual or not
     *
     * This determination is made by whether or not the quote has a shipping
     * item
     *
     * @param QuoteDetailsInterface $quoteDetails
     * @return bool
     */
    private function isVirtual(QuoteDetailsInterface $quoteDetails)
    {
        $items = $quoteDetails->getItems();
        foreach ($items as $item) {
            if ($item->getType() === 'shipping') {
                return true;
            }
        }
        return false;
    }
}
