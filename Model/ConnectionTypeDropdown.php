<?php

namespace XCode\ScheduledImport\Model;

class ConnectionTypeDropdown implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('FTP')], ['value' => 1, 'label' => __('JSON')]];
    }

    public function toArray()
    {
        return [0 => __('FTP'), 1 => __('JSON')];
    }

}