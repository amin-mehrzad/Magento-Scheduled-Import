<?php

namespace XCode\ScheduledImport\Model;

class IntervalDropdown implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [['value' => 5, 'label' => __('5 minutes')], ['value' => 10, 'label' => __('10 minutes')],['value' => 15, 'label' => __('15 minutes')], ['value' => 20, 'label' => __('20 minutes')],['value' => 30, 'label' => __('30 minutes')], ['value' => 60, 'label' => __('1 hour')]];
    }

    public function toArray()
    {
        return [5 => __('5 minutes'), 10 => __('10 minutes'),15 => __('15 minutes'), 10 => __('20 minutes'),30 => __('30 minutes'), 60 => __('1 hour')];
    }

}