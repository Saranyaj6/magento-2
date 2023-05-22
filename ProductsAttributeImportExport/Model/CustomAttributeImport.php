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
    }
    
    public function importAttribute($value)
    {
        $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
        $attributeSetId = $this->attributeSetFactory->create()->getDefaultId();
        $attributeGroupId = $this->groupFactory->create()->setAttributeSetId($attributeSetId)->getId();
        $attributeCode = $value['attribute_code'];

        try {
            $attribute = $this->attributeRepository->get($entityTypeId, $attributeCode);
        } catch (NoSuchEntityException $exception) {
            $attribute = $this->attributeFactory->create();
            $attribute->setAttributeCode($attributeCode);
            $attribute->setEntityTypeId($entityTypeId);
        }

        // Handle the 'label' attribute separately
        if (isset($value['label'])) {
            $attribute->setFrontendLabel($value['label']);
            unset($value['label']); // Remove 'label' from the $value array
        }

        foreach ($value as $key => $data) {
            $attribute->setData($key, $data);
        }

        $attribute->setIsUserDefined(true);

        try {
            $this->attributeRepository->save($attribute);
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


}
