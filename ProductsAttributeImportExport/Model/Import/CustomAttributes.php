<?php
namespace Codilar\ProductsAttributeImportExport\Model\Import;

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
use Codilar\ProductsAttributeImportExport\Model\CustomAttributeImport;

/**
 * Class ProductsAttributeImportExport
 */
class CustomAttributes extends AbstractEntity
{
    public const ENTITY_CODE = 'productsattributeimportexport';
    public const ENTITY_ID_COLUMN = 'attribute_code';
    public const TABLE = 'custom_product_attribute';

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
        'is_unique',
        'scope',
        'frontend_class',
        'frontend_input',
        'is_required',
        'options',
        'is_user_defined',
        'frontend_label',
        'note',
        'backend_type',
        'backend_model',
        'source_model',
        'validate_rules',
        'attribute_set_id',
        'attribute_group_id',

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

    $attribute_code = $rowData['attribute_code'] ?? '';
    $is_unique = (int) ($rowData['is_unique'] ?? 0);
    $scope = (int) ($rowData['scope'] ?? 0);
    $frontend_class = (int) ($rowData['frontend_class'] ?? 0);
    $frontend_input = $rowData['frontend_input'] ?? '';
    $is_required = (int) ($rowData['is_required'] ?? 0);
    $options = $rowData['options'] ?? '';
    $is_user_defined = (int) ($rowData['is_user_defined'] ?? 0);
    $frontend_label = $rowData['frontend_label'] ?? '';
    $note = $rowData['note'] ?? '';
    $backend_type = $rowData['backend_type'] ?? '';
    $backend_model = $rowData['backend_model'] ?? '';
    $source_model = $rowData['source_model'] ?? '';
    $validate_rules = $rowData['validate_rules'] ?? '';
    $attribute_set_id = (int) ($rowData['attribute_set_id'] ?? 0);
    $attribute_group_id = (int) ($rowData['attribute_group_id'] ?? 0);

        if (!$attribute_code) {
            $this->addRowError('Attribute_codeIsRequired', $rowNum);
        }
        if (!$frontend_label) {
            $this->addRowError('frontend_labelIsRequired', $rowNum);
        }

        if (!$frontend_input) {
            $this->addRowError('frontend_inputIsRequired', $rowNum);
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
            default:
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