<?php
namespace XCode\ScheduledImport\Controller\Adminhtml\Logs;

class Index extends \Magento\Backend\App\Action
{

  public function __construct(
	  \Magento\Backend\App\Action\Context $context,
	  \Magento\Framework\View\Result\PageFactory $resultPageFactory
  ) {
	   parent::__construct($context);
	   $this->resultPageFactory = $resultPageFactory;
  }

 
  public function execute()
  {
    $resultPage = $this->resultPageFactory->create();
    $resultPage->setActiveMenu('XCode_ScheduledImport::xcode');
    return  $resultPage ;
  }
}

?>
