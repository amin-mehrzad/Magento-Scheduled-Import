<?php

namespace XCode\ScheduledImport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory;

class Data extends AbstractHelper
{
    protected $_scopeConfig;
    protected $_reportCollectionFactory; 

    const XML_PATH_SCHADULED_IMPORT_CONNECTION_TYPE = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_connection_type';
    const XML_PATH_SCHADULED_IMPORT_HOST		    = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_host';
    const XML_PATH_SCHADULED_IMPORT_USERNAME     	= 'scheduledImport_tab/scheduledImport_setting/scheduledImport_username';
    const XML_PATH_SCHADULED_IMPORT_PASSWORD        = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_Password';
    const XML_PATH_SCHADULED_IMPORT_TIME_INTERVAL   = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_time_interval';
    const XML_PATH_SCHADULED_IMPORT_SKU_FIELD       = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_sku_field';
    const XML_PATH_SCHADULED_IMPORT_QTY_FIELD       = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_qty_field';
    const XML_PATH_SCHADULED_IMPORT_MSRP_FIELD      = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_msrp_field';
    const XML_PATH_SCHADULED_IMPORT_FILE_NAME       = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_file_name';
    const XML_PATH_SCHADULED_IMPORT_CUSTOM_MAP      = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_custom_mapping';
    const XML_PATH_SCHADULED_IMPORT_ACTIVE_DEBUG    = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_active_debug';
    const XML_PATH_SCHADULED_IMPORT_LOG_PATH        = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_log_file_path';
    const XML_PATH_SCHADULED_IMPORT_JSON_URL        = 'scheduledImport_tab/scheduledImport_setting/scheduledImport_log_json_url';


    public function __construct (
        Context $context,
        CollectionFactory  $reportCollectionFactory,
        ScopeConfigInterface $scopeConfig 
    ) {
    $this->_reportCollectionFactory = $reportCollectionFactory;
    parent::__construct($context);
    $this->_scopeConfig = $scopeConfig;
    }
    public function scheduledImport_connectionType() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_CONNECTION_TYPE);
    }
    public function scheduledImport_host() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_HOST);
    }
    public function scheduledImport_username() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_USERNAME);
    }
    public function scheduledImport_password() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_PASSWORD);
    }
    public function scheduledImport_timeInterval() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_TIME_INTERVAL);
    }
    public function scheduledImport_skuField() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_SKU_FIELD);
    }
    public function scheduledImport_qtyField() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_QTY_FIELD);
    }
    public function scheduledImport_msrpField() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_MSRP_FIELD);
    }
    public function scheduledImport_fileName() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_FILE_NAME);
    }
    public function scheduledImport_customMap() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_CUSTOM_MAP);
    }
    public function scheduledImport_activeDebug() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_ACTIVE_DEBUG);
    }
    public function scheduledImport_logPath() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_LOG_PATH);
    }
    public function scheduledImport_jsonURL() {
        return $this->_scopeConfig->getValue(self::XML_PATH_SCHADULED_IMPORT_JSON_URL);
    }

}