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
        EavSetupFactory $eavSetupFactory




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

    }

    public function importAttribute($value)
    {
        $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
        $attributeCode = $value['attribute_code'];

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


        try {
            $this->attributeRepository->save($attribute);

            $attributeSetId = $value['attribute_set_id'];
            $attributeGroupId = $value['attribute_group_id'];
            $eavSetup = $this->eavSetupFactory->create();

            $eavSetup->addAttributeToSet(
                'catalog_product',
                $attributeSetId,
                $attributeGroupId,
                $attributeCode,
                $sortOrder = null
            );

            // Get the attribute ID
            $attributeId = $attribute->getId();

            // Update the attribute set and group
            $attributeSetId = $value['attribute_set_id'];
            $attributeGroupId = $value['attribute_group_id'];
            $this->updateAttributeSet($attributeId, $attributeSetId, $attributeGroupId);



        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
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
}