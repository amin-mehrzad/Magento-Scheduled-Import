<?php

namespace XCode\ScheduledImport\Model;

class FieldDropdown implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('0')], ['value' => 1, 'label' => __('1')],['value' => 2, 'label' => __('2')], ['value' => 3, 'label' => __('3')],['value' => 4, 'label' => __('4')], ['value' => 5, 'label' => __('5')],['value' => 6, 'label' => __('6')], ['value' => 7, 'label' => __('7')],['value' => 8, 'label' => __('8')], ['value' => 9, 'label' => __('9')]];
    }

    public function toArray()
    {
        return [0 => __('0'), 1 => __('1'),2 => __('2'), 3 => __('3'),4 => __('4'), 5 => __('5'),6 => __('6'), 7 => __('7'),8 => __('8'), 9 => __('9')];
    }

}