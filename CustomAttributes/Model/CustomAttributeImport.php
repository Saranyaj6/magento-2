<?php
namespace Codilar\CustomAttributes\Model;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeInterfaceFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\GroupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductAttributeInterfaceFactory;




class CustomAttributeImport
{
    /**
     * @var ProductAttributeInterfaceFactory
     */
    private $productAttributeFactory;

    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var SetFactory
     */
    private $attributeSetFactory;

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var Action
     */
    private $action;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    private $logger;

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
        ProductAttributeInterfaceFactory $productAttributeFactory
       
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
        
        // Set the necessary data for the attribute
        $attribute->setAttributeCode($attributeCode);
        $attribute->setAttributeSetName($value['attribute_set_name']);
        $attribute->setAttributeGroup($value['attribute_group']);
        $attribute->setFrontendLabel($value['label']);
        $attribute->setFrontendInput($value['frontend_input']);
        $attribute->setFrontendClass($value['frontend_class']);
        $attribute->setIsRequired($value['is_required']);
        $attribute->setDefaultValue($value['default_value']);
        $attribute->setIsUnique($value['is_unique']);
        $attribute->setNote($value['note']);
        $attribute->setIsGlobal($value['attribute_scope']);
        $attribute->setIsVisible($value['is_visible']);
        $attribute->setIsSearchable($value['is_searchable']);
        $attribute->setIsFilterable($value['is_filterable']);
        $attribute->setIsComparable($value['is_comparable']);
        $attribute->setIsVisibleOnFront($value['is_visible_on_front']);
        $attribute->setIsHtmlAllowedOnFront($value['is_html_allowed_on_front']);
        $attribute->setIsUsedForPriceRules($value['is_used_for_price_rules']);
        $attribute->setIsFilterableInSearch($value['is_filterable_in_search']);
        $attribute->setUsedInProductListing($value['used_in_product_listing']);
        $attribute->setUsedForSortBy($value['used_for_sort_by']);
        $attribute->setIsVisibleInAdvancedSearch($value['is_visible_in_advanced_search']);
        $attribute->setPosition($value['position']);
        $attribute->setIsWysiwygEnabled($value['is_wysiwyg_enabled']);
        $attribute->setIsUsedForPromoRules($value['is_used_for_promo_rules']);
        $attribute->setIsRequiredInAdminStore($value['is_required_in_admin_store']);
        $attribute->setUsedInGrid($value['is_used_in_grid']);
        $attribute->setIsVisibleInGrid($value['is_visible_in_grid']);
        $attribute->setIsFilterableInGrid($value['is_filterable_in_grid']);
        $attribute->setSearchWeight($value['search_weight']);
        $attribute->setStoreLabel($value['store_label']);
        $attribute->setAttributeOptions($value['attribute_options']);
        $attribute->setStoreviewOptions($value['storeview_options']);
        $attribute->setSwatchOptions($value['swatch_options']);
        $attribute->setSwatchTextOptions($value['swatch_text_options']);
        $attribute->setUpdateProductPreviewImage($value['update_product_preview_image']);
        $attribute->setUseProductImageForSwatch($value['use_product_image_for_swatch']);
        // Save the attribute to the database
        try {
            $this->attributeRepository->save($attribute);

    } catch (\Exception $e) {
        $this->logger->critical($e->getMessage());
    }   
}



public function deleteAttribute($attributeCode)
{
    $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
    $attribute = $this->attributeRepository->get($entityTypeId, $attributeCode);

    if ($attribute->getId()) {
        try {
            $this->attributeRepository->delete($attribute);
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    } else {
        $this->logger->error(sprintf("Attribute with code %s not found", $attributeCode));
        return false;
    }
}



}



