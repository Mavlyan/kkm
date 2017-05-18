<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Discount extends Mage_Core_Helper_Abstract
{
    protected $_code = 'kkm';

    /** Returns item's data as array with properly calculated discount
     * @param $entity Mage_Sales_Model_Order | Mage_Sales_Model_Order_Invoice | Mage_Sales_Model_Order_Creditmemo
     * @param $itemSku
     * @param string $taxValue
     * @param string $taxAttributeCode
     * @param string $shippingTaxValue
     * @return array|mixed
     */
    public function getItemWithDiscount($entity, $itemSku, $taxValue = '', $taxAttributeCode = '', $shippingTaxValue = '')
    {
        $items = $this->getRecalculated($entity, $taxValue, $taxAttributeCode, $shippingTaxValue)['items'];

        return isset($items[$itemSku]) ? $items[$itemSku] : [];
    }

    /** Returns all items of the entity (order|invoice|creditmemo) with properly calculated discount and properly calculated Sum
     * @param $entity Mage_Sales_Model_Order | Mage_Sales_Model_Order_Invoice | Mage_Sales_Model_Order_Creditmemo
     * @param string $taxValue
     * @param string $taxAttributeCode Set it if info about tax is stored in product in certain attr
     * @param string $shippingTaxValue
     * @return array with calculated items and sum
     */
    public function getRecalculated($entity, $taxValue = '', $taxAttributeCode = '', $shippingTaxValue = '')
    {
        $generalHelper = Mage::helper($this->_code);

        $generalHelper->addLog("== START == Recalculation of entity prices. Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");

        $sumWithCartRuleDiscount = $entity->getSubtotal() + $entity->getDiscountAmount();
        $sumWithAllDiscount      = $entity->getGrandTotal() - $entity->getShippingAmount();

        $generalHelper->addLog("subtotal + discountAmount = {$sumWithCartRuleDiscount}; grandTotal - shippingAmount = {$sumWithAllDiscount}");

        $items = $entity->getAllItems();

        $itemsFinal = [];
        $itemsSum = 0.00;
        foreach ($items as $item) {
            if (!$item->getRowTotal()) {
                continue;
            }

            if ($taxAttributeCode) {
                $storeId  = $entity->getStoreId();
                $store    = $storeId ? Mage::app()->getStore($storeId) : Mage::app()->getStore();

                $taxValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(),
                    $taxAttributeCode, $store);
            }

            $percentDiscountValue = ($item->getRowTotal() - $item->getDiscountAmount()) / $sumWithCartRuleDiscount;
            $itemAfterDiscount    = $sumWithAllDiscount * $percentDiscountValue;

            $entityItem = $this->_calculateItem($item, $itemAfterDiscount, $taxValue);

            $generalHelper->addLog("Item calculation details:");
            $generalHelper->addLog("Item id: {$item->getId()}. Percentage: $percentDiscountValue. Item after discount: {$itemAfterDiscount}. Result of calc:");
            $generalHelper->addLog($entityItem);

            $itemsFinal[$item->getSku()] = $entityItem;
            $itemsSum += $entityItem['sum'];
        }

        $itemsSumDiff = round($sumWithAllDiscount - $itemsSum, 2);
        //if $itemsSumDiff > 0
        if(bccomp($itemsSumDiff, 0.00, 2) > 0) {
            $generalHelper->addLog("Sum of items do not equal to entity (order/invoice/creditmemo) um! Original sum of entity With All Discount: {$sumWithAllDiscount} Diff value: {$itemsSumDiff}. Items after calculations:");
            $generalHelper->addLog($itemsFinal);
        } elseif(bccomp($itemsSumDiff, 0.00, 2) < 0) {
            //else: $itemsSumDiff < 0
            $generalHelper->addLog("It seems rounding error. Sum of all items is greater than sumWithAllDiscount of entity. ItemsSumDiff: {$itemsSumDiff}");
            $itemsSumDiff = 0.0;
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origSum'        => $sumWithAllDiscount,
            'origGrandTotal' => floatval($entity->getGrandTotal())
        ];

        if (!$entity->getShippingMethod()) {
              $entity = $entity->getOrder();
        }

        $shippingItem = [
            'name'      => $entity->getShippingDescription(),
            'price'     => round($entity->getShippingAmount(), 2) + $itemsSumDiff,
            'quantity'  => 1.0,
            'sum'       => round($entity->getShippingAmount(), 2) + $itemsSumDiff,
            'tax'       => $shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)){
            $generalHelper->addLog("WARNING: Calculation error! Sum of items is not equal to grandTotal!");
        }
        $generalHelper->addLog("Final result of recalculation:");
        $generalHelper->addLog($receipt);
        $generalHelper->addLog("== STOP == Recalculation of entity prices. ");

        return $receipt;
    }

    protected function _calculateItem($item, $itemPriceAfterDiscount, $taxValue = '')
    {
        $qty = $item->getQty() ?: $item->getQtyOrdered();
        if (!$qty){
            throw new Exception('Divide by zero. Qty of the item is equal to zero! Item: ' . $item->getId());
        }

        $price = (float)bcdiv($itemPriceAfterDiscount, $qty, 2);

        $entityItem = [
            'price' => $price,
            'name' => $item->getName(),
            'quantity' => $this->slyFloor($qty),
            'sum' => floatval($price) * $qty,
            'tax' => $taxValue,
        ];

        return $entityItem;
    }

    /**Validation method. It sums up all items and compares it to grandTotal.
     * @param array $receipt
     * @return bool True if all items price equal to grandTotal. False - if not.
     */
    protected function _checkReceipt(array $receipt)
    {
        $sum = array_reduce($receipt['items'], function($carry, $item) {
            $carry += $item['sum'];
            return $carry;
        });

        return bcsub($sum, $receipt['origGrandTotal'], 2) === '0.00';
    }

    public function slyFloor($val, $precision = 2)
    {
        $factor  = 1.00;
        $divider = pow(10.0, $precision);

        if ($val < 0) {
            $factor = -1.00;
        }

        return (floor(abs($val) * $divider) / $divider) * $factor;
    }
}