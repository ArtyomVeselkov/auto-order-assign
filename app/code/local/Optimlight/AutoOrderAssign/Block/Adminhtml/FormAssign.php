<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_MetaBase_Block_Adminhtml_Mage_Sales_Order_Edit
 */

class Optimlight_AutoOrderAssign_Block_Adminhtml_FormAssign extends Mage_Adminhtml_Block_Abstract
{
    const EVENT_BEFORE_RENDER = 'opt_aoa_block_assign_form_render_before';

    /**
     * @var null|array
     */
    protected $_config = null;

    /**
     * @param array $data
     */
    protected function dispatchRenderEvent($data)
    {
        Mage::dispatchEvent(self::EVENT_BEFORE_RENDER, ['block' => $this, 'data' => $data]);
    }

    /**
     * @return mixed|string
     */
    protected function _toHtml()
    {
        if (!Optimlight_AutoOrderAssign_Helper_Data::isEnabled()) {
            return '';
        }
        $config = $this->dumpConfig();
        $this->dispatchRenderEvent($config);
        return parent::_toHtml();
    }

    /**
     * @param bool $reloadFlag
     * @param bool $asJson
     * @return array
     */
    public function dumpConfig($reloadFlag = false, $asJson = false)
    {
        if (is_null($this->_config) || $reloadFlag) {
            $html = $this->renderBlankForm();
            $action = Optimlight_AutoOrderAssign_Helper_Data::getAdminhtmlLocationUrl('adminhtml/optAoaAssign/reassign', '');
            $show = Optimlight_AutoOrderAssign_Helper_Data::isEnabled();
            $orderId = $this->getCurrentOrderId();
            $history = Optimlight_AutoOrderAssign_Helper_Data::getReferenceHistory($orderId, true);
            $this->_config = [
                'html' => $html,
                'action' => $action,
                'appendTo' => '.wrapper > .footer',
                'show' => $show,
                'showCtrl' => 'button[name="' . Optimlight_AutoOrderAssign_Helper_Source::BLOCK_BUTTON_SHOW_FORM_ASSIGN . '_value"]',
                'orderId' => $orderId,
                'history' => $history ? $history : 'No history.',
                'messages' => 'No messages.'
            ];
        }
        $result = $this->_config;
        if ($asJson) {
            $result = json_encode($result);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function renderBlankForm()
    {
        /** @var Optimlight_AutoOrderAssign_Block_Adminhtml_FormAssign_Blank $block */
        $block = $this->getLayout()->createBlock('opt_aoa/adminhtml_formAssign_blank');
        $result = $block->toHtml();
        return $result;
    }

    /**
     * @return bool|int
     */
    public function getCurrentOrderId()
    {
        $result = false;
        $order = Optimlight_AutoOrderAssign_Helper_Data::getCurrentOrder();
        if ($order) {
            $result = $order->getId();
        }
        return $result;
    }
}