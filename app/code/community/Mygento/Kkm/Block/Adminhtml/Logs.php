<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright © 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Logs extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_logs';
        $this->_blockGroup = 'kkm';
        $this->_headerText = Mage::helper('kkm')->__('Logs Viewer');

        parent::__construct();
        $this->removeButton('add');
    }
}
