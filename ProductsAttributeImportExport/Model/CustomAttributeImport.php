<?php

namespace Codilar\ProductsAttributeImportExport\Model;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterfaceFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\GroupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductAttributeInterfaceFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Model\Entity\Type;


class CustomAttributeImport
{
    private $attributeRepository;
    private $attributeFactory;
    private $eavConfig;
    private $productFactory;
    private $attributeSetFactory;
    private $groupFactory;
    private $action;
    private $config;
    private $repository;
    private $logger;
    private $productAttributeFactory;
    private $eavSetupFactory;
    protected $attributeSetRepository;
    protected $attributeGroupRepository;
    protected $attributeSetInterfaceFactory;
    protected $entityType;

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        ProductFactory $productFactory,
        SetFactory $attributeSetFactory,
        GroupFactory $groupFactory,
        Action $action,
        Config $config,
        \Psr\Log\LoggerInterface $logger,
        Config $eavConfig,
        Repository $repository,
        AttributeInterfaceFactory $attributeFactory,
        ProductAttributeInterfaceFactory $productAttributeFactory,
        EavSetupFactory $eavSetupFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeSetInterfaceFactory $attributeSetInterfaceFactory,
        Type $entityType

    ) {
        $this->attributeRepository = $attributeRepository;
        $this->productFactory = $productFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->groupFactory = $groupFactory;
        $this->action = $action;
        $this->config = $config;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->eavConfig = $eavConfig;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->attributeSetInterfaceFactory = $attributeSetInterfaceFactory;
        $this->entityType = $entityType;

    }

    public function importAttribute($value)
    {

        $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
        $attributeCode = $value['attribute_code'];
        $attributeSetName = $value['attribute_set_name'];
        $attributeGroupName = $value['attribute_group_name'];


        //check attribute set is exist or not and create the attribute set
        $attributeSetExists = $this->attributeSetFactory->create();
        $attributeSetExists->load($attributeSetName,  'attribute_set_name');
        if (!$attributeSetExists->getId()) {
           $attributeSetId = $this->createAttributeSet($entityTypeId, $attributeSetName);
        }
        else{
            // Retrieve attribute set Id
            $attributeSetId = $this->retrieveAttributeSetId($entityTypeId , $attributeSetName);
        }

        //create a attribute group
        $this->addAttributeGroup($attributeSetId, $attributeGroupName);
        // Retrieve attribute group Id
        $attributeGroupId = $this->retrieveAttributeGroupId($attributeSetId, $attributeGroupName);

        try {
            $attribute = $this->attributeRepository->get($entityTypeId, $attributeCode);
        } catch (NoSuchEntityException $exception) {
            $attribute = $this->attributeFactory->create();
            $attribute->setAttributeCode($attributeCode);
            $attribute->setEntityTypeId($entityTypeId);
        }

        foreach ($value as $key => $data) {
            $attribute->setData($key, $data);
        }

        $attribute->setFrontendInput($value['frontend_input']);
        $attribute->setAttributeSetId($attributeSetId);
        $attribute->setAttributeGroupId($attributeGroupId);

        try {
            // save the attribute
            $this->attributeRepository->save($attribute);

            //add attribute to the attribute set
            $this->addAttributeSet($attributeCode, $attributeSetId, $attributeGroupId);

            // Update the attribute set and group
            $attributeId = $attribute->getId();  // Get the attribute ID
            $this->updateAttributeSet($attributeId, $attributeSetId, $attributeGroupId);

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function createAttributeSet($entityTypeId, $attributeSetName)
    {
        $attributeSet = $this->attributeSetInterfaceFactory->create();
        $attributeSet->setAttributeSetName($attributeSetName);
        $attributeSet->setEntityTypeId($entityTypeId);

        $this->attributeSetRepository->save($attributeSet); //save attribute set
        $attributeSetId = $this->retrieveAttributeSetId($entityTypeId , $attributeSetName);    //  Retrieve attribute set Id
        return $attributeSetId;
    }


    public function addAttributeGroup($attributeSetId, $attributeGroupName)
    {
            $attributeGroup = $this->groupFactory->create();
            $attributeGroup->setAttributeSetId($attributeSetId);
            $attributeGroup->setAttributeGroupName($attributeGroupName);
            $this->attributeGroupRepository->save($attributeGroup);
    }



    public function addAttributeSet($attributeSetId, $attributeGroupId, $attributeCode)
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttributeToSet(
            'catalog_product',
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            $sortOrder = null
        );
    }



    public function updateAttributeSet($attributeId, $attributeSetId, $attributeGroupId)
    {
        try {
            $eavSetup = $this->eavSetupFactory->create();
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeId,
                'attribute_set_id',
                $attributeSetId
            );
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeId,
                'attribute_group_id',
                $attributeGroupId
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function deleteAttribute($row)
    {
        $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
        try {
            $attribute = $this->attributeRepository->get($entityTypeId, $row);
            if ($attribute->getId()) {
                $this->attributeRepository->delete($attribute);
                return true;
            } else {
                $this->logger->error(sprintf("Attribute with code %s not found", $row));
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    public function retrieveAttributeSetId($entityTypeId , $attributeSetName){
        $attributeSetId = null;
        $entityType = $this->entityType->load($entityTypeId);
        $attributeSetCollection = $this->attributeSetFactory->create()
            ->getCollection()
            ->addFieldToFilter('attribute_set_name', $attributeSetName)
            ->addFieldToFilter('entity_type_id', $entityType->getId())
            ->setPageSize(1);

        if ($attributeSetCollection->getSize()) {
            $attributeSet = $attributeSetCollection->getFirstItem();
            $attributeSetId = $attributeSet->getId();
        }

        return $attributeSetId;
    }

    public function retrieveAttributeGroupId($attributeSetId, $attributeGroupName){

        $obj = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Catalog\Model\Config $config */
        $config= $obj->get('Magento\Catalog\Model\Config');
        $attributeGroupId = $config->getAttributeGroupId($attributeSetId, $attributeGroupName);
        return $attributeGroupId;

    }
}
