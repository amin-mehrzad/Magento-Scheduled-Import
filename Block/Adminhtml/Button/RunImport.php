<?php
namespace XCode\ScheduledImport\Block\Adminhtml\Button;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use XCode\ScheduledImport\Block\Adminhtml\DisplayBlock;
use Magento\Backend\Block\Widget\Context;

class RunImport extends Generic implements ButtonProviderInterface
{

    // protected $context;
    // protected $displayBlock;
    // public function __construct(
    //     Context $context,
    //     DisplayBlock $displayBlock
    // ) {
    //     $this->context = $context;
    //     $this->displayBlock = $displayBlock;

    // }
    /**
     * get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Run Importing Manually'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            // 'on_click' => $this->runImporting(),
            'class' => 'primary',
            'sort_order' => 10
        ];
    }
    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        //$this->importingManually();
        return $this->getUrl('*/*/').'?run=true';
    }

    // public function runImporting()
    // {
    //     // sprintf("location.href = '%s';", $this->getBackUrl());

    // }
    
}