<?php

namespace XCode\ScheduledImport\Cron;

use Magento\CatalogInventory\Model\Stock\StockItemRepository as StockItem;
use Magento\Catalog\Model\Product as Product;
use Magento\Catalog\Model\ProductFactory as ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action as Action;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use XCode\ScheduledImport\Helper\Data;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\Filesystem\Driver\File;
use \Magento\Framework\Filesystem\Io\Ftp;
use \Magento\Framework\UrlInterface;

class Import extends \Magento\Framework\App\Action\Action
{
    protected $helper;
    protected $stockItem;
    protected $product;
    protected $_fileCsv;
    protected $ftp;
    protected $_file;
    protected $_pageFactory;
    protected $directory_list;
    protected $_urlInterface;
    protected $ProductFactory;
    protected $Action;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $ProductRepository,
        Data $helper,
        StockItem $stockItem,
        Product $product,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\File\Csv $fileCsv,
        Ftp $ftp,
        File $file,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        DirectoryList $directory_list,
        UrlInterface $urlInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        ProductFactory $ProductFactory,
        Action $Action,
        \Magento\Store\Model\StoreManagerInterface $StoreManager,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku

    ) {
        $this->helper = $helper;
        $this->ProductRepository = $ProductRepository;
        $this->stockItem = $stockItem;
        $this->product = $product;
        $this->_moduleReader = $moduleReader;
        $this->_fileCsv = $fileCsv;
        $this->ftp = $ftp;
        $this->_file = $file;
        $this->_pageFactory = $pageFactory;
        $this->directory_list = $directory_list;
        $this->_urlInterface = $urlInterface;
        $this->stockRegistry = $stockRegistry;
        $this->ProductFactory = $ProductFactory;
        $this->Action = $Action;
        $this->StoreManager = $StoreManager;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        return parent::__construct($context);
    }

    public function execute()
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
        $logPath = BP . "/var/log/" . $this->helper->scheduledImport_logPath();
        $jsonURL = $this->helper->scheduledImport_jsonURL();
        $writer = new \Zend\Log\Writer\Stream($logPath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(__METHOD__);

        $logger->info('Connection type is : ' . $connectionType);
        $logger->info('The Host name is : ' . $host);
        $logger->info('Username is : ' . $username);
        $logger->info('Password is : ' . $password);
        $logger->info('The time interval is : ' . $timeInterval);
        $logger->info('SKU field is : ' . $skuField);
        $logger->info('Qty field is : ' . $qtyField);
        $logger->info('MSRP field is : ' . $msrpField);
        $logger->info('The File name is : ' . $fileName);
        $logger->info('Custom Code : ' . $customMap);
        $logger->info('Is Debugging mode active? : ' . $activeDebug);
        $logger->info('Path of .log  file is : ' . $logPath);
        $logger->info('JSON URL is : ' . $jsonURL);
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        if ($connectionType == 0) {

            $directory = $this->_moduleReader->getModuleDir('etc', 'XCode_ScheduledImport');
            $open = $this->ftp->open(
                array(
                    'host' => $host,
                    'user' => $username,
                    'password' => $password,
                    //'ssl' => true,
                    //'passive' => true
                )
            );
            $tempFile = $directory . '/' . 'temp.csv';
            $file = '/' . $fileName;
            $this->ftp->read($file, $tempFile);
            $this->ftp->close();
            $logger->info($file);
            $logger->info($tempFile);
            $logger->alert('IMPORTING DATA IS IN PROCESS ....');
            if ($this->_file->isExists($tempFile)) {
                $fileData = $this->_fileCsv->getData($tempFile);
                for ($i = 1; $i < count($fileData); $i++) {
                    $fileSku = $fileData[$i][$skuField];
                    $fileQty = $fileData[$i][$qtyField];
                    $fileMsrp = $fileData[$i][$msrpField];
                    $fileWeight = $fileData[$i][$weightField];
                    // $setResults = $this->setEverything($fileSku, $fileQty, $fileMsrp);

                    // $logger->alert('...................');

                    $response = array(
                        "status" => "skipped",
                        "sku" => $fileSku,
                        "message" => "Product Quantity with the sku $fileSku did not change, no need to update",
                        "old_qty" => $fileQty,
                        "new_qty" => $fileQty,
                        "old_msrp" => 0,
                        "new_msrp" => 0,
                        "old_isinstock" => "unchanged",
                        "new_isinstock" => "unchanged",
                        "old_weight" => $fileWeight,
                        "new_weight" => $fileWeight,
                    );

                    try {
                        $stockItem = $this->stockRegistry->getStockItemBySku($fileSku);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $stockItem = false;
                    }
                    if (!$stockItem) {
                        $response["status"] = "missing_sku";
                        $response["message"] = "Product with the sku $fileSku does not exist";
                        //return $response;
                        $setResults = $response;
                        $logger->info($setResults["message"] . '.');
                        continue;
                    }
                    //get old_qty and stock status
                    $response["old_qty"] = $stockItem->getQty();
                    $response["old_isinstock"] = $stockItem->getIsInStock();

                    $saleableQty = $response["old_qty"];
                    if (!empty($this->getSalableQuantityDataBySku->execute($fileSku))) {

                        $saleableQty = $this->getSalableQuantityDataBySku->execute($fileSku)[0]["qty"];
                    } else {

                    }
                    $reservation = $response["old_qty"] - $saleableQty;

                    $xQty = $response["old_qty"] - $saleableQty;

                    if ($fileQty < $xQty || true) {
                        $fileQty = $fileQty + $xQty;
                    }

                    $response["new_qty"] = $fileQty;

                    //check if qty did not change, return predefined response
                    if ($fileQty != $response["old_qty"]) {

                        $stockItem->setQty($fileQty);
                        $stockItem->setIsInStock((bool) $fileQty);

                        $this->stockRegistry->updateStockItemBySku($fileSku, $stockItem);

                        $response["new_qty"] = $stockItem->getQty();
                        $response["new_isinstock"] = $stockItem->getIsInStock();

                        if ($response["new_qty"] != $fileQty) {
                            $response["status"] = "error";
                            $response["message"] = "Something went wrong. Updated qty does not match with final result";
                        } else {
                            $response["status"] = "success";
                            $response["message"] = "Successfully updated sku $fileSku qty from {$response["old_qty"]} to {$response["new_qty"]}";
                        }
                    }

                    // $setAttributeResults = $this->setSingleAttributeBySku($fileSku, 'msrp', $fileMsrp);
                    //print_r($setAttributeResults);

                    $responseX = array(
                        "status" => "skipped",
                        "attribute" => 'msrp',
                        "message" => "Value did not change, no need to update",
                        "old_value" => $fileMsrp,
                        "new_value" => $fileMsrp,
                    );

                    try {
                        $productRep = $this->ProductRepository->get($fileSku);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $productRep = false;
                    }
                    if ($productRep) {
                        $responseX["old_value"] = floatval($productRep->getData('msrp'));
                        $responseX["new_value"] = floatval($fileMsrp);
                        if ($responseX["old_value"] != $responseX["new_value"]) {
                            $storeId = $this->StoreManager->getStore()->getId();
                            $this->Action->updateAttributes([$productRep->getId()], ['msrp' => $responseX["new_value"]], $storeId);
                            $responseX["new_value"] = floatval($fileMsrp);
                            $responseX["status"] = "success";
                        }
                    }

                    $setAttributeResults = $responseX;

                    $response["old_msrp"] = $setAttributeResults["old_value"];
                    $response["new_msrp"] = $setAttributeResults["new_value"];

                    if ($setAttributeResults["status"] == "success") {
                        $response["message"] .= " Updated msrp from {$response["old_msrp"]} to {$response["new_msrp"]}";
                        if ($response["status"] != "error") {
                            $response["status"] = "success";
                        }
                        $setResults = $response;
                        $logger->info($setResults["message"] . '.');
                    }

                    $responseXX = array(
                        "status" => "skipped",
                        "attribute" => 'weight',
                        "message" => "Value did not change, no need to update",
                        "old_value" => $fileWeight,
                        "new_value" => $fileWeight,
                    );

                    try {
                        $productRep = $this->ProductRepository->get($fileSku);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $productRep = false;
                    }
                    if ($productRep) {
                        $responseXX["old_value"] = floatval($productRep->getData('weight'));
                        $responseXX["new_value"] = floatval($fileWeight);
                        if ($responseXX["old_value"] != $responseXX["new_value"]) {
                            $storeId = $this->StoreManager->getStore()->getId();
                            $this->Action->updateAttributes([$productRep->getId()], ['weight' => $responseX["new_value"]], $storeId);
                            $responseXX["new_value"] = floatval($fileWeight);
                            $responseXX["status"] = "success";
                        }
                    }

                    $setAttributeResultsXX = $responseXX;

                    $response["old_weight"] = $setAttributeResultsXX["old_value"];
                    $response["new_new"] = $setAttributeResultsXX["new_value"];

                    if ($setAttributeResultsXX["status"] == "success") {
                        $response["message"] .= " Updated msrp from {$response["old_weight"]} to {$response["new_weight"]}";
                        if ($response["status"] != "error") {
                            $response["status"] = "success";
                        }
                        $setResults = $response;
                        $logger->info($setResults["message"] . '.');
                    }
                }

            }
            $this->_file->deleteFile($tempFile);
        }

    }

}
