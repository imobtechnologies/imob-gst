# Imob Gst

## Main Functionalities

- The module allows to apply Indian GST rates on your cart items
- There are three methods you can configure GST rate calculation
    - Global : allows to add a single GST rate on all items in store
    - Category based : allows to add separate GST rate for each category
    - Product based : allows to add separate GST rate for each product (Requires Pro Version)
- GST will be applied on all taxable items, non taxable items will not be included with tax calculation
- Bifurcation of tax rates will be displayed on cart page, checkout page and order details page
- Allows to add GSTIN to transactional PDF documents like invoice, shipment and credit memo
- Admin can select business origin state from configuration
- Allows to add default GST rate in configuration section in case any category or product is missing GST rate

## Configuration

 - Enable : Enable or disable Imob GST module functionality
 - GSTIN : Add your business GSTIN number
 - CIN : Add your business CIN number
 - PAN : Add your business PAN number
 - GST Calculation Type : Select GST calculation as per your requirements
 - Origin State : Select business origin state. This will be used to bifurcate SGST+CGST and IGST rates
 - Show GSTIN on PDF : Add/Remove GSTIN from transactional documents
