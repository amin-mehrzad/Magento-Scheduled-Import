<?php
namespace XCode\ScheduledImport\Block\Adminhtml\Button;
use Magento\Backend\Block\Widget\Context;
/* use Magento\Framework\Exception\NoSuchEntityException;
use XCode\ScheduledImport\Helper\Data;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\Filesystem\Io\Ftp;
use \Magento\Framework\Filesystem\Driver\File;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as StockItem;
use Magento\Catalog\Model\Product as Product;
use \Magento\Framework\UrlInterface;
*/
class Generic
{
    /*protected $helper;
	protected $stockItem;
	protected $product;
	protected $_fileCsv;
	protected $ftp;
	protected $_file;
	protected $directory_list;
	protected $_urlInterface;
    */
    protected $context;
    public function __construct(
        Context $context
       /* ProductRepositoryInterface $productRepository,
		Data $helper,
		StockItem $stockItem,
		Product $product,
		\Magento\Framework\Module\Dir\Reader $moduleReader,
		\Magento\Framework\File\Csv $fileCsv,
		Ftp $ftp,
		File $file,
		DirectoryList $directory_list,
		//array $data = [],
        UrlInterface $urlInterface 
        */
    ) {
        $this->context = $context;
       /* $this->helper = $helper;
		$this->productRepository = $productRepository;
		$this->stockItem = $stockItem;
		$this->product = $product;
		$this->_moduleReader = $moduleReader;
		$this->_fileCsv = $fileCsv;
		$this->ftp = $ftp;
		$this->_file = $file;
		$this->directory_list = $directory_list;
		$this->_urlInterface = $urlInterface;
        */
    }
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
    /*public function importingManually()
	{
		$connectionType = $this->helper->scheduledImport_connectionType();
		$host = $this->helper->scheduledImport_host();
		$username = $this->helper->scheduledImport_username();
		$password = $this->helper->scheduledImport_password();
		$timeInterval = $this->helper->scheduledImport_timeInterval();
		$msrpField = $this->helper->scheduledImport_msrpField();
		$qtyField = $this->helper->scheduledImport_qtyField();
		$skuField = $this->helper->scheduledImport_skuField();
		$fileName = $this->helper->scheduledImport_fileName();
		$customMap = $this->helper->scheduledImport_customMap();
		$activeDebug = $this->helper->scheduledImport_activeDebug();
		$logPath = $this->helper->scheduledImport_logPath();

		$writer = new \Zend\Log\Writer\Stream(BP . $logPath);
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info(__METHOD__);

		$logger->info('Connection type is : ' .$connectionType);
		$logger->info('The Host name is : ' .$host);
		$logger->info('Username is : ' .$username);
		$logger->info('Password is : ' .$password);
		$logger->info('The time interval is : ' .$timeInterval);
		$logger->info('SKU field is : ' .$skuField);
		$logger->info('Qty field is : ' .$qtyField);
		$logger->info('MSRP field is : ' .$msrpField);
		$logger->info('The File name is : ' .$fileName);
		$logger->info('Custom Code : ' .$customMap);
		$logger->info('Is Debugging mode active? : ' .$activeDebug);
		$logger->info('Path of .log  file is : ' .$logPath);
		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		
	

		$directory = $this->_moduleReader->getModuleDir('etc', 'XCode_ScheduledImport'); 
		
		$open = $this->ftp->open(
			array(
				'host' => $host,
				'user' => $username,
				'password' => $password
				//'ssl' => true,
				//'passive' => true
			)
		);
	//	$logger->info($directory);
		$tempFile = $directory.'/'. 'temp.csv';
		$file = '/' . $fileName ;
		$this->ftp->read( $file , $tempFile);
		$logger->info($file);
		

		
		
		if ($this->_file->isExists($tempFile)) {
			$logger->info($tempFile);
			$logger->alert('IMPORTING DATA IS IN PROCESS ....');
			if ($connectionType==0){
				$fileData = $this->_fileCsv->getData($tempFile);	
    			for($i=1; $i<count($fileData); $i++) {
					$sku=$fileData[$i][$skuField];
					$quantity=$fileData[$i][$qtyField];
					$productItem = $this->productRepository->get($sku);
					$productId=$this->product->getIdBySku($sku);
					if($productId){
						$productStock = $this->stockItem->get($productId);
						$productQty = $productStock->getQty();
					}
					if($productStock->getIsInStock()){
						$isInStock='In Stock';
					}else{
						$isInStock='Out Of Stock';
					}
					if($quantity>0){
						$newIsInStock='In Stock';
					}else{
						$newIsInStock='Out Of Stock';
					}
					$msrp=$productItem->getMsrp();
					$newMsrp=$fileData[$i][$msrpField];
					if($newMsrp){
				 		$productItem->setMsrp($newMsrp);
					}
					// $productItem->setQuantityAndStockStatus(['qty' => $quantity, 'is_in_stock' => (bool)true]);
					$productItem->setQuantityAndStockStatus(['qty' => $quantity]);
					$this->productRepository->save($productItem);
					$productItem->save();
					$logger->info('sku: '.$sku.' quantity is :' .$productQty .' status is :'. $isInStock. '  MSRP is :  '. $msrp.' ----Changed to---->>>  Qty:  '.$quantity.'  - Status: '. $newIsInStock .'  - MSRP: '.$newMsrp);
					//var_dump($fileData[$i]);
				}
			}else{
				$fileData = file_get_contents($tempFile);
				$jsonData = json_decode($fileData);
				for($i=1;$i<count($jsonData); $i++ ){
					$sku=$jsonData[$i]->SKU;
					$quantity=$jsonData[$i]->QTY;
					$productItem = $this->productRepository->get($sku);
					$productId=$this->product->getIdBySku($sku);
					if($productId){
						$productStock = $this->stockItem->get($productId);
						$productQty = $productStock->getQty();
					}
					if($productStock->getIsInStock()){
						$isInStock='In Stock';
					}else{
						$isInStock='Out Of Stock';
					}
					if($quantity>0){
						$newIsInStock='In Stock';
					}else{
						$newIsInStock='Out Of Stock';
					}
					$msrp=$productItem->getMsrp();
					$newMsrp=$jsonData[$i]->MSRP;;
					if($newMsrp){
				 		$productItem->setMsrp($newMsrp);
					}
					$productItem->setQuantityAndStockStatus(['qty' => $quantity]);
					$this->productRepository->save($productItem);
					$productItem->save();
					$logger->info('sku: '.$sku.' quantity is :' .$productQty .' status is :'. $isInStock. '  MSRP is :  '. $msrp.' ----Changed to---->>>  Qty:  '.$quantity.'  - Status: '. $newIsInStock .'  - MSRP: '.$newMsrp);
					//var_dump($jsonData[$i]);
//					$logger->info($sku);
					
				}	
			}
		}  
		$this->_file->deleteFile($tempFile);
		$this->ftp->close();
		//return __();
	} */
}