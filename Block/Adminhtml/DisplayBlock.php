<?php
namespace XCode\ScheduledImport\Block\Adminhtml;


use XCode\ScheduledImport\Helper\Data;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\Filesystem\Io\Ftp;
use \Magento\Framework\Filesystem\Driver\File;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as StockItem;
use Magento\Catalog\Model\Product as Product;
use \Magento\Framework\UrlInterface;
use Magento\Catalog\Model\ProductFactory as ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action as Action;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class DisplayBlock extends \Magento\Backend\Block\Template

{
	protected $helper;
	protected $stockItem;
	protected $product;
	protected $_fileCsv;
	protected $ftp;
	protected $_file;
	protected $directory_list;
	protected $_urlInterface;
	protected $ProductFactory;
	protected $Action;


	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		Data $helper,
		StockItem $stockItem,
		Product $product,
		\Magento\Framework\Module\Dir\Reader $moduleReader,
		\Magento\Framework\File\Csv $fileCsv,
		Ftp $ftp,
		File $file,
		DirectoryList $directory_list,
		array $data = [],
		UrlInterface $urlInterface,
		\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
		ProductFactory $ProductFactory,
		Action $Action,
		\Magento\Store\Model\StoreManagerInterface $StoreManager,
		\Magento\Catalog\Api\ProductRepositoryInterface $ProductRepository,
		GetSalableQuantityDataBySku $getSalableQuantityDataBySku
		)
	{	
		$this->helper = $helper;
		$this->stockItem = $stockItem;
		$this->product = $product;
		$this->_moduleReader = $moduleReader;
		$this->_fileCsv = $fileCsv;
		$this->ftp = $ftp;
		$this->_file = $file;
		$this->directory_list = $directory_list;
		$this->_urlInterface = $urlInterface;
		$this->stockRegistry = $stockRegistry;
		$this->ProductFactory = $ProductFactory;
		$this->Action = $Action;
		$this->StoreManager = $StoreManager;
		$this->ProductRepository = $ProductRepository;
		$this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
		parent::__construct($context,$data);
	}

	public function setEverything($sku, $qty, $msrp, $weight){
	
		$response = array(
			"status" 		=> "skipped",
			"sku"			=> $sku,
			"message" 		=> "Quantity did not change, no need to update",
			"old_qty"		=> $qty,
			"new_qty"		=> $qty,
			"old_msrp"		=> 0,
			"new_msrp"		=> 0,
			"old_isinstock" => "unchanged",
			"new_isinstock" => "unchanged",
			"old_weight"	=> $weight,
			"new_weight"	=> $weight
		);
  
		try{
			$stockItem = $this->stockRegistry->getStockItemBySku($sku);
		} catch (\Magento\Framework\Exception\NoSuchEntityException $e){
			$stockItem = false;
		}
		if(!$stockItem){
			$response["status"] = "missing_sku";
			$response["message"] = "Product with the sku $sku does not exist";
			return $response;
		}
		//get old_qty and stock status
		$response["old_qty"] = $stockItem->getQty();
		$response["old_isinstock"] = $stockItem->getIsInStock();
		
		//check if qty did not change, return predefined response
		//error_log(print_r($this->getSalableQuantityDataBySku->execute($sku),true));
		$saleableQty = $response["old_qty"];
		if(!empty($this->getSalableQuantityDataBySku->execute($sku))){
			$saleableQty = $this->getSalableQuantityDataBySku->execute($sku)[0]["qty"];
		} else {
			
		}
		
		$xQty = $response["old_qty"] - $saleableQty ;
		if($qty < $xQty || true){
			$qty = $qty + $xQty;
		}
		$response["new_qty"] = $qty;
		if($qty != $response["old_qty"]){ //override
			
			$stockItem->setQty($qty);
			$stockItem->setIsInStock((bool)$qty);
			
			$this->stockRegistry->updateStockItemBySku($sku, $stockItem);
			
			$response["new_qty"] = $stockItem->getQty();
			$response["new_isinstock"] = $stockItem->getIsInStock();
			
			if($response["new_qty"] != $qty){
				$response["status"] = "error";
				$response["message"] = "Something went wrong. Updated qty does not match with final result";
			} else {
				$response["status"] = "success";
				$response["message"]= "Successfully updated sku $sku qty from {$response["old_qty"]} to {$response["new_qty"]}";
			}
		}
		
		
		// update msrp
		$setAttributeResults = $this::setSingleAttributeBySku($sku, 'msrp', $msrp);
		$response["old_msrp"] = $setAttributeResults["old_value"];
		$response["new_msrp"] = $setAttributeResults["new_value"];
		if($setAttributeResults["status"] == "success"){
			$response["message"] .=" Updated msrp from {$response["old_msrp"]} to {$response["new_msrp"]}";
			if ($response["status"] != "error"){
				$response["status"] = "success";
			}
		}
		// update weight
		$setAttributeResults = $this::setSingleAttributeBySku($sku, 'weight', $weight);
		$response["old_weight"] = $setAttributeResults["old_value"];
		$response["new_new"] = $setAttributeResults["new_value"];
		if($setAttributeResults["status"] == "success"){
			$response["message"] .=" Updated weight from {$response["old_weight"]} to {$response["new_weight"]}";
			if ($response["status"] != "error"){
				$response["status"] = "success";
			}
		}
		
		return $response;
	}
	
	
	public function setInventoryBySku($sku, $qty, $msrp, $weight){
	
		$response = array(
			"status" 		=> "skipped",
			"sku"			=> $sku,
			"message" 		=> "Quantity did not change, no need to update",
			"old_qty"		=> $qty,
			"new_qty"		=> $qty,
			"old_isinstock" => "-",
			"new_isinstock" => "-",
			"old_weight" => $weight,
			"new_weight" => $weight
		);
  
		try{
			$stockItem = $this->stockRegistry->getStockItemBySku($sku);
		} catch (\Magento\Framework\Exception\NoSuchEntityException $e){
			$stockItem = false;
		}
		if(!$stockItem){
			$response["status"] = "missing_sku";
			$response["message"] = "Product with the sku $sku does not exist";
			return $response;
		}
		//get old_qty and stock status
		$response["old_qty"] = $stockItem->getQty();
		$response["old_isinstock"] = $stockItem->getIsInStock();
		
		//check if qty did not change, return predefined response
		$saleableQty = $this->getSalableQuantityDataBySku->execute($sku)[0]["qty"];
		$reservation = $response["old_qty"] - $saleableQty;
		
		$xQty = $response["old_qty"] - $saleableQty ;
		
		if($qty < $xQty || true){
			$qty = $qty + $xQty;
		}
		
		if($qty == $response["old_qty"]){ 
			$response["new_isinstock"] = $response["old_isinstock"];
			return $response;
		}
		
		$stockItem->setQty($qty);
		$stockItem->setIsInStock((bool)$qty);
		
		$this->stockRegistry->updateStockItemBySku($sku, $stockItem);
		
		$response["new_qty"] = $stockItem->getQty();
		$response["new_isinstock"] = $stockItem->getIsInStock();
		
		if($response["new_qty"] != $qty){
			$response["status"] = "error";
			$response["message"] = "Something went wrong. Updated qty does not match with final result";
		} else {
			$response["status"] = "success";
			$response["message"]= "Successfully updated sku $sku qty from {$response["old_qty"]} to {$response["new_qty"]}";
		}
	
		
		return $response;
	}
	
	public function setSingleAttributeBySku($sku, $singleAttribute, $value){
	
		$response = array(
			"status" 		=> "skipped",
			"attribute"		=> $singleAttribute,
			"message" 		=> "Value did not change, no need to update",
			"old_value"		=> $value,
			"new_value"		=> $value
		);
		
		try{
			$productRep = $this->ProductRepository->get($sku);
		} catch (\Magento\Framework\Exception\NoSuchEntityException $e){
			$productRep = false;
		}
		if($productRep){
			$response["old_value"] = floatval($productRep->getData($singleAttribute));
			$response["new_value"] = floatval($value);
			if($response["old_value"] != $response["new_value"]){
				$storeId = $this->StoreManager->getStore()->getId();
				$this->Action->updateAttributes([$productRep->getId()], [$singleAttribute=>$response["new_value"]], $storeId);
				$response["new_value"] =  floatval($value);
				$response["status"] = "success";
			}
		}
		return $response;
	}
	

	public function getContents()
	{
		$logPath = $this->helper->scheduledImport_logPath();
		$basePath = $this->directory_list->getPath('base');
		$line = '';
		$log = '';
		$f = fopen($basePath ."/var/log/". $logPath , 'r');
		$cursor = -1;
		fseek($f, $cursor, SEEK_END);
		$char = fgetc($f);
		for($i=0;$i<600;$i++){
			
			//Trim trailing newline chars of the file
 			
			while ($char === "\n" || $char === "\r") {
    		fseek($f, $cursor--, SEEK_END);
    		$char = fgetc($f);
			}
			
 			//Read until the start of file or first newline char
 			
			while ($char !== false && $char !== "\n" && $char !== "\r") {
    		
     		//Prepend the new char
     		
    		$line = $char . $line;
    		fseek($f, $cursor--, SEEK_END);
    		$char = fgetc($f);
			}

			if (strpos($line, 'ALERT (1)') !== false) {
				return __($line);
			}

			
			$line='<br>'.$line.'<br>';
		}
		
		return __($line);
	}
	public function importingManually()
	{
		$connectionType = $this->helper->scheduledImport_connectionType();
		$host = $this->helper->scheduledImport_host();
		$username = $this->helper->scheduledImport_username();
		$password = $this->helper->scheduledImport_password();
		$timeInterval = $this->helper->scheduledImport_timeInterval();
		$msrpField = $this->helper->scheduledImport_msrpField();
		$qtyField = $this->helper->scheduledImport_qtyField();
		$skuField = $this->helper->scheduledImport_skuField();
		$weightField = 10;
		$fileName = $this->helper->scheduledImport_fileName();
		$customMap = $this->helper->scheduledImport_customMap();
		$activeDebug = $this->helper->scheduledImport_activeDebug();
		$logPath = BP ."/var/log/". $this->helper->scheduledImport_logPath();
		$jsonURL = $this->helper->scheduledImport_jsonURL();

		$writer = new \Zend\Log\Writer\Stream($logPath);
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
		$logger->info('JSON URL is : ' .$jsonURL);
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		

		if ($connectionType==0) {

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
			$tempFile = $directory.'/'. 'temp.csv';
			$file = '/' . $fileName ;
			$this->ftp->read( $file , $tempFile);
			$this->ftp->close();
			$logger->info($file);

			$logger->info($tempFile);
			$logger->alert('IMPORTING DATA IS IN PROCESS ....');

			if ($this->_file->isExists($tempFile)){
				echo '
				<style>
					.x-success{font-weight:bold; color:green}
					.x-skipped{font-weight:bold; color:orange}
					.x-missing_sku{ color:lightgrey}
					.x-error{font-weight:bold; color:red}
				</style>';
				echo '<table id="titles"><thead>
				<tr>
				  <th>SKU</th>
				  <th>Importing Status</th>
				  <th>Old Quantity</th>
				  <th>New Quantity</th> 
				  <th>Old Stock Status</th>
				  <th>New Stock Status</th>
				  <th>Old MSRP</th>
				  <th>New MSRP</th>
				  <th>Old Weight</th>
				  <th>New Weight</th>
				</thead></tr><tbody>';
				
				

				$fileData = $this->_fileCsv->getData($tempFile);
								
    			for($i=1; $i<count($fileData); $i++) {
					$fileSku  = $fileData[$i][$skuField];
					$fileQty  = $fileData[$i][$qtyField];
					$fileMsrp = $fileData[$i][$msrpField];
					$fileWeight = $fileData[$i][$weightField];
					
					$setResults = $this::setEverything($fileSku, $fileQty, $fileMsrp, $fileWeight);
					//$setResults = $this::setInventoryBySku($fileSku, $fileQty);
					//$setAttributeResults = $this::setSingleAttributeBySku($fileSku, 'msrp', $fileMsrp);
					//print_r($setAttributeResults);
						
							echo '<tr class="x-'.$setResults["status"].'">
							<td style="font-weight:bold">'.$setResults["sku"].'</td>
							<td>'. $setResults["status"] .'</td>
							<td>'. $setResults["old_qty"] .'</td>
							<td>'. $setResults["new_qty"] .'</td>
							<td>'. $setResults["old_isinstock"] .'</td>
							<td>'. $setResults["new_isinstock"] .'</td>
							<td>'. $setResults["old_msrp"] .'</td>
							<td>'. $setResults["new_msrp"] .'</td>
							<td>'. $setResults["old_weight"] .'</td>
							<td>'. $setResults["new_weight"] .'</td>
							</tr>';
							$logger->info($setResults["message"]. '.');
					
			}
			echo '</tbody></table>';
		}
		}
		/*else{
			$fileData = file_get_contents($tempFile);
			$jsonData = json_decode($fileData);

			// $ch = curl_init($jsonURL);
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// $jsonData = curl_exec($ch);
			// curl_close($ch);
	
			for($i=1;$i<count($jsonData); $i++ ){
				$sku=$jsonData[$i]->SKU;
				$quantity=$jsonData[$i]->QTY;
				$product = $this->productRepository->get($sku);
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
				$msrp=$product->getMsrp();
				$newMsrp=$jsonData[$i]->MSRP;;
				if($newMsrp){
					 $product->setMsrp($newMsrp);
				}
				$product->setQuantityAndStockStatus(['qty' => $quantity, 'is_in_stock' => ($quantity > 0 ? 1 : 0)]);
				$this->productRepository->save($product);
				$product->save();
				$logger->info('sku: '.$sku.' quantity is :' .$productQty .' status is :'. $isInStock. '  MSRP is :  '. $msrp.' ----Changed to---->>>  Qty:  '.$quantity.'  - Status: '. $newIsInStock .'  - MSRP: '.$newMsrp);	
			}	
		}
*/		
		$this->_file->deleteFile($tempFile);
	}
}