<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Helper_Source
 */

class Optimlight_AutoOrderAssign_Helper_Source
{
    const ENTITY_TYPE_ORDER = 1;
    const ENTITY_TYPE_CUSTOMER = 2;

    const REFERENCE_STATE_ERROR = 0;
    const REFERENCE_STATE_OK = 1;

    const BLOCK_BUTTON_SHOW_FORM_ASSIGN = 'opt_aoa_reassign_ctrl';

    const EVENT_ORDER_CHECK_AFTER = 'opt_aoa_order_check_after';
    const EVENT_ASSIGN_ORDER_BEFORE = 'opt_aoa_order_assign_before';
    const EVENT_ASSIGN_ORDER_AFTER = 'opt_aoa_order_assign_after';

    /**
     * @param bool|string $label
     * @return array
     */
    public static function getOrderReferenceButtonData($label = false)
    {
        $label = $label ? $label : 'Reassign';
        return [
            'id' => self::BLOCK_BUTTON_SHOW_FORM_ASSIGN,
            'sort_order' => 3,
            'button_data' => array(
                'class' => 'optimlight-aoa-reassign-button',
                'label' => $label,
                'onclick' => "javascript:optimlightAoaFormAssign.callBy(this, 'show');",
                'name' => self::BLOCK_BUTTON_SHOW_FORM_ASSIGN . '_value'
            )
        ];
    }
}