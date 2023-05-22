<?php
namespace Codilar\CustomAttributes\Model\Import;

use Exception;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Codilar\CustomAttributes\Model\CustomAttributeImport;

/**
 * Class CustomAttributes
 */
class CustomAttributes extends AbstractEntity
{
    const ENTITY_CODE = 'customattributes';
    const ENTITY_ID_COLUMN = 'attribute_code';
    const TABLE = 'custom_product_attribute';




    

    /**
     * If we should check column names
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     */
    protected $logInHistory = true;


    protected $CustomAttributeImport;

    /**
     * Permanent entity columns.
     */
    protected $_permanentAttributes = [
        'attribute_code'
    ];

    /**
     * Valid column names
     */
    protected $validColumnNames = [
        'attribute_code',
        'attribute_set_name',
        'attribute_group',
        'frontend_input',
        'frontend_class',
        'is_required',
        'default_value',
        'is_unique',
        'note',
        'attribute_scope',
        'is_visible',
        'is_searchable',
        'is_filterable',
        'is_comparable',
        'is_visible_on_front',
        'is_html_allowed_on_front',
        'is_used_for_price_rules',
        'is_filterable_in_search',
        'used_in_product_listing',
        'used_for_sort_by',
        'is_visible_in_advanced_search',
        'position',
        'is_wysiwyg_enabled',
        'is_used_for_promo_rules',
        'is_required_in_admin_store',
        'is_used_in_grid',
        'is_visible_in_grid',
        'is_filterable_in_grid',
        'search_weight',
        'label',
        'store_label',
        'attribute_options',
        'storeview_options',
        'swatch_options',
        'swatch_text_options',
        'update_product_preview_image',
        'use_product_image_for_swatch',

    ];

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * CustomAttributes constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param AttributeFactory $attributeFactory
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportHelper $importExportData,
        Data $importData,
        ResourceConnection $resource,
        Helper $resourceHelper,
        AttributeFactory $attributeFactory,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CustomAttributeImport $CustomAttributeImport
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->attributeFactory = $attributeFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->errorAggregator = $errorAggregator;
        $this->CustomAttributeImport = $CustomAttributeImport;

        

    }

    /**
    * Entity type code getter.
    *
    * @return string
    */
    public function getEntityTypeCode()
    {   
        
        return static::ENTITY_CODE;
    }
    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {   
        
        return $this->validColumnNames;
    }
    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     *
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        
        $attribute_code= $rowData['attribute_code'] ?? '';
        $attribute_set_name = $rowData['attribute_set_name'] ?? '';
        $attribute_group = $rowData['attribute_group'] ?? '';
        $frontend_input = $rowData['frontend_input'] ?? '';
        $frontend_class = (int)($rowData['frontend_class'] ?? 0);
        $is_required = (int)($rowData['is_required'] ?? 0);
        $default_value = $rowData['default_value'] ?? '';
        $is_unique = (int)($rowData['is_unique'] ?? 0);
        $note = $rowData['note'] ?? '';
        $attribute_scope = (int)($rowData['attribute_scope'] ?? 0);
        $is_visible = (int)($rowData['is_visible'] ?? 0);
        $is_searchable = (int)($rowData['is_searchable'] ?? 0);
        $is_filterable = (int)($rowData['is_filterable'] ?? 0);
        $is_comparable = (int)($rowData['is_comparable'] ?? 0);
        $is_visible_on_front = (int)($rowData['is_visible_on_front'] ?? 0);
        $is_html_allowed_on_front = (int)($rowData['is_html_allowed_on_front'] ?? 0);
        $is_used_for_price_rules = (int)($rowData['is_used_for_price_rules'] ?? 0);
        $is_filterable_in_search = (int)($rowData['is_filterable_in_search'] ?? 0);
        $used_in_product_listing = (int)($rowData['used_in_product_listing'] ?? 0);
        $used_for_sort_by = (int)($rowData['used_for_sort_by'] ?? 0);
        $is_visible_in_advanced_search = (int)($rowData['is_visible_in_advanced_search'] ?? 0);
        $position = (int)($rowData['position'] ?? 0);
        $is_wysiwyg_enabled = (int)($rowData['is_wysiwyg_enabled'] ?? 0);
        $is_used_for_promo_rules = (int)($rowData['is_used_for_promo_rules'] ?? 0);
        $is_required_in_admin_store = (int)($rowData['is_required_in_admin_store'] ?? 0);
        $is_used_in_grid = (int)($rowData['is_used_in_grid'] ?? 0);
        $is_visible_in_grid = (int)($rowData['is_visible_in_grid'] ?? 0);
        $is_filterable_in_grid = (int)($rowData['is_filterable_in_grid'] ?? 0);
        $search_weight = (int)($rowData['search_weight'] ?? 0);  
        $label = $rowData['label'] ?? '';
        $store_label = $rowData['store_label'] ?? '';
        $attribute_options = $rowData['attribute_options'] ?? '';
        $storeview_options = $rowData['storeview_options'] ?? '';
        $swatch_options = $rowData['swatch_options'] ?? '';
        $swatch_text_options = $rowData['swatch_text_options'] ?? '';
        $update_product_preview_image = (int)($rowData['update_product_preview_image'] ?? 0); 
        $use_product_image_for_swatch = (int)($rowData['use_product_image_for_swatch'] ?? 0); 

        if (!$attribute_code) {
            $this->addRowError('Attribute_codeIsRequired', $rowNum);
        }
        if (!$label) {
            $this->addRowError('LabelIsRequired', $rowNum);
        }

        if (!$attribute_set_name) {
            $this->addRowError('Attribute_set_nameIsRequired', $rowNum);
        }

        if (!$frontend_input) {
            $this->addRowError('Frontend_inputIsRequired', $rowNum);
        }

        if (!$store_label) {
            $this->addRowError('Store_labelIsRequired', $rowNum);
        }

        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
    
        $this->_validatedRows[$rowNum] = true;
        
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    
    }

    /**
     * Import data
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function _importData(): bool
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->deleteEntity();
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->saveAndReplaceEntity();
                break;
            case Import::BEHAVIOR_APPEND:
                $this->saveAndReplaceEntity();
                break;
        }
        
        return true;
    }

    /**
     * Delete entities
     *
     * @return bool
     */
    private function deleteEntity(): bool
    {
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->validateRow($rowData, $rowNum);

                if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    $rowId = $rowData[static::ENTITY_ID_COLUMN];
                    $rows[] = $rowId;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                }
            }
        }

        if ($rows) {
            return $this->deleteEntityFinish(array_unique($rows));
        }

        return false;
    }

    /**
     * Save and replace entities
     *
     * @return void
     */
    private function saveAndReplaceEntity()
    {
        $behavior = $this->getBehavior();
        $rows = [];
        
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityList = [];

            foreach ($bunch as $rowNum => $row) {
                if (!$this->validateRow($row, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);

                    continue;
                }

                $rowId = $row[static::ENTITY_ID_COLUMN];
                $rows[] = $rowId;
                $columnValues = [];

                foreach ($this->getAvailableColumns() as $columnKey) {
                    $columnValues[$columnKey] = $row[$columnKey];
                }

                $entityList[$rowId][] = $columnValues;
                $this->countItemsCreated += (int) !isset($row[static::ENTITY_ID_COLUMN]);
                $this->countItemsUpdated += (int) isset($row[static::ENTITY_ID_COLUMN]);
            }

            if (Import::BEHAVIOR_REPLACE === $behavior) {
                if ($rows && $this->deleteEntityFinish(array_unique($rows))) {
                    $this->saveEntityFinish($entityList);
                }
            } elseif (Import::BEHAVIOR_APPEND === $behavior) {
                $this->saveEntityFinish($entityList);
            }
        }
    }
    
    

    /**
     * Save entities
     *
     * @param array $entityData
     *
     * @return bool
     */
    private function saveEntityFinish(array $entityData): bool
    {
        
        if ($entityData) {
            $tableName = $this->connection->getTableName(static::TABLE);
            $rows = [];
            
            foreach ($entityData as $entityRows) {
                foreach ($entityRows as $row) {
                    $rows[] = $row;
                    $this->CustomAttributeImport->importAttribute($row);
                }
            }

            if ($rows) {
                $this->connection->insertOnDuplicate($tableName, $rows, $this->getAvailableColumns());
                
                return true;
            }
            
            return false;
        }
        
    }

    /**
     * Delete entities
     *
     * @param array $entityIds
     *
     * @return bool
     */
    private function deleteEntityFinish(array $entityIds): bool
    {
        if ($entityIds) {
            try {   
                    foreach ($entityIds as $row) {
                        $this->CustomAttributeImport->deleteAttribute($row);
                    }
                    $this->countItemsDeleted += $this->connection->delete(
                    $this->connection->getTableName(static::TABLE),
                    $this->connection->quoteInto(static::ENTITY_ID_COLUMN . ' IN (?)', $entityIds)
                );

                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }

}