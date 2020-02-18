<?php
/**
 * A Magento 2 module named Imob/Gst
 * Copyright (C) 2020 iMob Technologies. All rights reserved.
 */

namespace Imob\Gst\Plugin\Magento\Sales\Model\Order\Pdf;

use Imob\Gst\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Zend_Pdf_Exception;
use Zend_Pdf_Font;
use Zend_Pdf_Page;

class AbstractPdf
{

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Filesystem\Directory\ReadInterface
     */
    protected $_rootDirectory;

    /**
     * Invoice constructor.
     * @param Data $helper
     * @param Filesystem $filesystem
     */
    public function __construct(
        Data $helper,
        Filesystem $filesystem
    ) {
        $this->_rootDirectory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->helper = $helper;
    }

    /**
     * Add GSTIN on transaction PDFs
     * Number will be added on Invoice, Shipment and Credit memo PDFs
     *
     * @param \Magento\Sales\Model\Order\Pdf\AbstractPdf $subject
     * @param $result
     * @param $page
     * @param $text
     * @return mixed
     * @throws Zend_Pdf_Exception
     */
    public function afterInsertDocumentNumber(
        \Magento\Sales\Model\Order\Pdf\AbstractPdf $subject,
        $result,
        $page,
        $text
    ) {
        if ($this->helper->isEnabled() && $this->helper->showGSTINOnPDF() && $this->helper->getGSTIN()) {
            $this->insertGSTIN($page, __('GSTIN: ') . $this->helper->getGSTIN(), $subject);
        }
        return $result;
    }

    /**
     * @param Zend_Pdf_Page $page
     * @param $text
     * @param $subject
     * @throws Zend_Pdf_Exception
     */
    public function insertGSTIN(Zend_Pdf_Page $page, $text, $subject)
    {
        $font = Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/GnuFreeFont/FreeSerif.ttf')
        );
        $docHeader = $subject->getDocHeaderCoordinates();
        $page->drawText($text, $subject->getAlignRight($text, 130, 440, $font, 10), $docHeader[1] - 15, 'UTF-8');
    }
}
