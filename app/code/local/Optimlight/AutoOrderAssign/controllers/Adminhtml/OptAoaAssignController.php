<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Adminhtml_OptAoaAssignController
 */

class Optimlight_AutoOrderAssign_Adminhtml_OptAoaAssignController extends Mage_Adminhtml_Controller_Action
{
    /** @var Optimlight_AutoOrderAssign_Model_Assign  */
    protected $_assign = null;

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->_assign = Mage::getModel('opt_aoa/assign');
    }

    /**
     *
     */
    public function reassignAction()
    {
        $data = $this->reassign();
        $this->send($data);
    }

    /**
     * @return array
     * @throws Varien_Exception
     */
    public function reassign()
    {
        $request = $this->getRequest();
        $buffer = false;
        $dataRaw = $request->getParam('data');
        $orderId = false;
        $customerIdentifier = false;
        if ($dataRaw) {
            $data = Optimlight_AutoOrderAssign_Helper_Data::tryJsonDecode($dataRaw);
            if (is_array($data)) {
                $customerIdentifier = @$data['customer_identifier'];
                $orderId = @$data['order_id'];
            }
        }
        $result = [
            'messages' => ['Unknown error.'],
            'status' => false
        ];
        if ($orderId && $customerIdentifier) {
            $buffer = $this->_assign->reassignOrder($orderId, $customerIdentifier);
        } else {
            $messages = [];
            if (!$orderId) {
                $messages[] = "Not valid Order ID.";
            }
            if (!$customerIdentifier) {
                $messages[] = "Not valid Customer's email or ID.";
            }
            $result = [
                'messages' => $messages,
                'status' => false
            ];
        }
        if ($buffer && is_a($buffer, 'Optimlight_AutoOrderAssign_Model_Pocket')) {
            /** @var Optimlight_AutoOrderAssign_Model_Pocket $buffer */
            $result['messages'] = array_merge([$buffer->getResult(), "* * * * * *"], $buffer->errors);
            $result['status'] = $buffer->getResultFlag();
        }
        return $result;
    }

    /**
     * @param array $data
     * @param array $messages
     */
    protected function send($data, $messages = [])
    {
        $data = $this->packResult($data, $messages);
        $this->sendJson($data);
    }

    /**
     * @param array $data
     * @param array $messages
     * @return mixed
     */
    protected function packResult($data, $messages = [])
    {
        if (isset($data['messages']) && is_array($data['messages'])) {
            $data['messages'] = array_merge($messages, $data['messages']);
            $data['messages'] = implode("\n", $data['messages']);
        }
        return $data;
    }

    /**
     * @param array $data
     */
    protected function sendJson($data)
    {
        $json = Zend_Json::encode($data);
        $this->getResponse()
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody($json);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('opt_aoa/request');
    }
}