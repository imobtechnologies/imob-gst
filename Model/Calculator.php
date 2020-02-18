<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Model;

use Imob\Gst\Model\Calculation\GstCalculator;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;
use Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\AbstractCalculator;
use Magento\Tax\Model\Calculation\CalculatorFactory;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\TaxDetails\AppliedTax;
use Magento\Tax\Model\TaxDetails\AppliedTaxRate;
use Magento\Tax\Model\TaxDetails\TaxDetails;

/**
 * Vertex Tax Calculator
 */
class Calculator
{
    /**
     * Tax Details factory
     *
     * @var TaxDetailsInterfaceFactory
     */
    protected $taxDetailsDataObjectFactory;

    /**
     * Tax configuration object
     *
     * @var Config
     */
    protected $config;

    /**
     * Tax calculation model
     *
     * @var Calculation
     */
    protected $calculationTool;

    /**
     * Array to hold discount compensations for items
     *
     * @var array
     */
    protected $discountTaxCompensations;

    /**
     * Tax details item factory
     *
     * @var TaxDetailsItemInterfaceFactory
     */
    protected $taxDetailsItemDataObjectFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * Tax Class Management
     *
     * @var TaxClassManagementInterface
     */
    protected $taxClassManagement;
    /**
     * Calculator Factory
     *
     * @var CalculatorFactory
     */
    protected $calculatorFactory;
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * Item code to Item object array.
     *
     * @var QuoteDetailsItemInterface[]
     */
    private $keyedItems;
    /**
     * Parent item code to children item array.
     *
     * @var QuoteDetailsItemInterface[][]
     */
    private $parentToChildren;

    /**
     * @param Calculation $calculation
     * @param CalculatorFactory $calculatorFactory
     * @param Config $config
     * @param TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory
     * @param TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory
     * @param StoreManagerInterface $storeManager
     * @param TaxClassManagementInterface $taxClassManagement
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        Calculation $calculation,
        CalculatorFactory $calculatorFactory,
        Config $config,
        TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory,
        TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory,
        StoreManagerInterface $storeManager,
        TaxClassManagementInterface $taxClassManagement,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->calculationTool = $calculation;
        $this->calculatorFactory = $calculatorFactory;
        $this->config = $config;
        $this->taxDetailsDataObjectFactory = $taxDetailsDataObjectFactory;
        $this->taxDetailsItemDataObjectFactory = $taxDetailsItemDataObjectFactory;
        $this->storeManager = $storeManager;
        $this->taxClassManagement = $taxClassManagement;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateTax(
        QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true
    ) {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }

        // initial TaxDetails data
        $taxDetailsData = [
            TaxDetails::KEY_SUBTOTAL => 0.0,
            TaxDetails::KEY_TAX_AMOUNT => 0.0,
            TaxDetails::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT => 0.0,
            TaxDetails::KEY_APPLIED_TAXES => [],
            TaxDetails::KEY_ITEMS => [],
        ];
        $items = $quoteDetails->getItems();
        if (empty($items)) {
            return $this->taxDetailsDataObjectFactory->create()
                ->setSubtotal(0.0)
                ->setTaxAmount(0.0)
                ->setDiscountTaxCompensationAmount(0.0)
                ->setAppliedTaxes([])
                ->setItems([]);
        }
        $this->computeRelationships($items);

        /**
         * Calculate with GST class
         */
        $calculator = $this->calculatorFactory->create(
            'GST',
            $storeId,
            $quoteDetails->getBillingAddress(),
            $quoteDetails->getShippingAddress(),
            $this->taxClassManagement->getTaxClassId($quoteDetails->getCustomerTaxClassKey(), 'customer'),
            $quoteDetails->getCustomerId()
        );

        $processedItems = [];
        /** @var QuoteDetailsItemInterface $item */
        foreach ($this->keyedItems as $item) {
            if (isset($this->parentToChildren[$item->getCode()])) {
                $processedChildren = [];
                foreach ($this->parentToChildren[$item->getCode()] as $child) {
                    $processedItem = $this->processItem($child, $calculator, $round);
                    $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
                    $processedItems[$processedItem->getCode()] = $processedItem;
                    $processedChildren[] = $processedItem;
                }
                $processedItem = $this->calculateParent($processedChildren, $item->getQuantity());
                $processedItem->setCode($item->getCode());
                $processedItem->setType($item->getType());
            } else {
                $processedItem = $this->processItem($item, $calculator, $round);
                $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
            }
            $processedItems[$processedItem->getCode()] = $processedItem;
        }

        $taxDetailsDataObject = $this->taxDetailsDataObjectFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $taxDetailsDataObject,
            $taxDetailsData,
            TaxDetailsInterface::class
        );
        $taxDetailsDataObject->setItems($processedItems);
        return $taxDetailsDataObject;
    }

    /**
     * Computes relationships between items, primarily the child to parent relationship.
     *
     * @param QuoteDetailsItemInterface[] $items
     * @return void
     */
    private function computeRelationships($items)
    {
        $this->keyedItems = [];
        $this->parentToChildren = [];
        foreach ($items as $item) {
            if ($item->getParentCode() === null) {
                $this->keyedItems[$item->getCode()] = $item;
            } else {
                $this->parentToChildren[$item->getParentCode()][] = $item;
            }
        }
    }

    /**
     * Calculate item tax with customized rounding level
     *
     * @param QuoteDetailsItemInterface $item
     * @param AbstractCalculator $calculator
     * @param bool $round
     * @return TaxDetailsItemInterface
     */
    protected function processItem(
        QuoteDetailsItemInterface $item,
        GstCalculator $calculator,
        $round = true
    ) {
        $quantity = $this->getTotalQuantity($item);
        return $calculator->calculate($item, $quantity, $round);
    }

    /**
     * Calculates the total quantity for this item.
     *
     * What this really means is that if this is a child item, it return the parent quantity times
     * the child quantity and return that as the child's quantity.
     *
     * @param QuoteDetailsItemInterface $item
     * @return float
     */
    protected function getTotalQuantity(QuoteDetailsItemInterface $item)
    {
        if ($item->getParentCode()) {
            $parentQuantity = $this->keyedItems[$item->getParentCode()]->getQuantity();
            return $parentQuantity * $item->getQuantity();
        }
        return $item->getQuantity();
    }

    /**
     * Add row total item amount to subtotal
     *
     * @param array $taxDetailsData
     * @param TaxDetailsItemInterface $item
     * @return array
     */
    protected function aggregateItemData($taxDetailsData, TaxDetailsItemInterface $item)
    {
        $taxDetailsData[TaxDetails::KEY_SUBTOTAL]
            = $taxDetailsData[TaxDetails::KEY_SUBTOTAL] + $item->getRowTotal();

        $taxDetailsData[TaxDetails::KEY_TAX_AMOUNT]
            = $taxDetailsData[TaxDetails::KEY_TAX_AMOUNT] + $item->getRowTax();

        $taxDetailsData[TaxDetails::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT] =
            $taxDetailsData[TaxDetails::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT]
            + $item->getDiscountTaxCompensationAmount();

        $itemAppliedTaxes = $item->getAppliedTaxes();
        if ($itemAppliedTaxes === null) {
            return $taxDetailsData;
        }
        $appliedTaxes = $taxDetailsData[TaxDetails::KEY_APPLIED_TAXES];
        foreach ($itemAppliedTaxes as $taxId => $itemAppliedTax) {
            if (!isset($appliedTaxes[$taxId])) {
                //convert rate data object to array
                $rates = [];
                $rateDataObjects = $itemAppliedTax->getRates();
                foreach ($rateDataObjects as $rateDataObject) {
                    $rates[$rateDataObject->getCode()] = [
                        AppliedTaxRate::KEY_CODE => $rateDataObject->getCode(),
                        AppliedTaxRate::KEY_TITLE => $rateDataObject->getTitle(),
                        AppliedTaxRate::KEY_PERCENT => $rateDataObject->getPercent(),
                    ];
                }
                $appliedTaxes[$taxId] = [
                    AppliedTax::KEY_AMOUNT => $itemAppliedTax->getAmount(),
                    AppliedTax::KEY_PERCENT => $itemAppliedTax->getPercent(),
                    AppliedTax::KEY_RATES => $rates,
                    AppliedTax::KEY_TAX_RATE_KEY => $itemAppliedTax->getTaxRateKey(),
                ];
            } else {
                $appliedTaxes[$taxId][AppliedTax::KEY_AMOUNT] += $itemAppliedTax->getAmount();
            }
        }

        $taxDetailsData[TaxDetails::KEY_APPLIED_TAXES] = $appliedTaxes;
        return $taxDetailsData;
    }

    /**
     * Calculate row information for item based on children calculation
     *
     * @param TaxDetailsItemInterface[] $children
     * @param int $quantity
     * @return TaxDetailsItemInterface
     */
    protected function calculateParent($children, $quantity)
    {
        $rowTotal = 0.00;
        $rowTotalInclTax = 0.00;
        $rowTax = 0.00;
        $taxableAmount = 0.00;

        foreach ($children as $child) {
            $rowTotal += $child->getRowTotal();
            $rowTotalInclTax += $child->getRowTotalInclTax();
            $rowTax += $child->getRowTax();
            $taxableAmount += $child->getTaxableAmount();
        }

        $price = $this->calculationTool->round($rowTotal / $quantity);
        $priceInclTax = $this->calculationTool->round($rowTotalInclTax / $quantity);

        $taxDetailsItemDataObject = $this->taxDetailsItemDataObjectFactory->create()
            ->setPrice($price)
            ->setPriceInclTax($priceInclTax)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setRowTax($rowTax);

        return $taxDetailsItemDataObject;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCalculatedRate(
        $productTaxClassID,
        $customerId = null,
        $storeId = null
    ) {
        return $this->getRate($productTaxClassID, $customerId, $storeId, true);
    }

    /**
     * Calculate rate based on default parameter
     *
     * @param int $productTaxClassID
     * @param int|null $customerId
     * @param string|null $storeId
     * @param bool $isDefault
     * @return float
     */
    protected function getRate(
        $productTaxClassID,
        $customerId = null,
        $storeId = null,
        $isDefault = false
    ) {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }
        if (!$isDefault) {
            $addressRequestObject = $this->calculationTool->getRateRequest(null, null, null, $storeId, $customerId);
        } else {
            $addressRequestObject = $this->calculationTool->getDefaultRateRequest($storeId, $customerId);
        }
        $addressRequestObject->setProductClassId($productTaxClassID);
        return $this->calculationTool->getRate($addressRequestObject);
    }

    /**
     * {@inheritdoc}
     */
    public function getCalculatedRate(
        $productTaxClassID,
        $customerId = null,
        $storeId = null
    ) {
        return $this->getRate($productTaxClassID, $customerId, $storeId);
    }
}
