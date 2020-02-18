<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Model\Calculation;

use InvalidArgumentException;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Model\Calculation\AbstractCalculator;
use Magento\Tax\Model\Calculation\RowBaseCalculator;
use Magento\Tax\Model\Calculation\TotalBaseCalculator;
use Magento\Tax\Model\Calculation\UnitBaseCalculator;

class CalculatorFactory extends \Magento\Tax\Model\Calculation\CalculatorFactory
{
    /**
     * Identifier constant for gst based calculation
     */
    const CALC_GST_BASE = 'GST';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create new calculator
     *
     * @param string $type Type of calculator
     * @param int $storeId
     * @param CustomerAddress $billingAddress
     * @param CustomerAddress $shippingAddress
     * @param null|int $customerTaxClassId
     * @param null|int $customerId
     * @return AbstractCalculator
     * @throws InvalidArgumentException
     */
    public function create(
        $type,
        $storeId,
        CustomerAddress $billingAddress = null,
        CustomerAddress $shippingAddress = null,
        $customerTaxClassId = null,
        $customerId = null
    ) {
        switch ($type) {
            case self::CALC_UNIT_BASE:
                $className = UnitBaseCalculator::class;
                break;
            case self::CALC_ROW_BASE:
                $className = RowBaseCalculator::class;
                break;
            case self::CALC_TOTAL_BASE:
                $className = TotalBaseCalculator::class;
                break;
            /**
             * Added GST Calculator.
             * This will be used if GST module is enabled and Country is India.
             */
            case self::CALC_GST_BASE:
                $className = GstCalculator::class;
                break;
            default:
                throw new InvalidArgumentException('Unknown calculation type: ' . $type);
        }
        /** @var AbstractCalculator $calculator */
        $calculator = $this->objectManager->create($className, ['storeId' => $storeId]);
        if (null != $shippingAddress) {
            $calculator->setShippingAddress($shippingAddress);
        }
        if (null != $billingAddress) {
            $calculator->setBillingAddress($billingAddress);
        }
        if (null != $customerTaxClassId) {
            $calculator->setCustomerTaxClassId($customerTaxClassId);
        }
        if (null != $customerId) {
            $calculator->setCustomerId($customerId);
        }
        return $calculator;
    }
}
