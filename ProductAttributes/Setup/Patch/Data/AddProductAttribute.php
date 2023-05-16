<?php
namespace Codilar\ProductAttributes\Setup\Patch\Data;
 
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
 
class AddProductAttribute implements DataPatchInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /** @var EavSetupFactory */
    private $eavSetupFactory;
 
    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }
    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $rowData = [
            // attribute properties here
        ];
        $this->getAttribute($rowData);
                
    }
 
    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
 
    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    public function getAttribute($rowData){
        // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        // $logger = new \Zend_Log();
        // $logger->addWriter($writer);
        // $logger->info(json_encode($rowData['attribute_code'], true));
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ($rowData as $value) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $rowData['attribute_code'],
                [
                    'attribute_set' => $rowData['attribute_set_name'],
                    'group' => $rowData['attribute_group'],
                    'input' => $rowData['frontend_input'],
                    'frontend_class' => $rowData['frontend_class'],
                    'required' => $rowData['is_required'],
                    'user_defined' => true,
                    'default' => $rowData['default_value'],
                    'unique' => $rowData['is_unique'],
                    'note' => $rowData['note'],
                    'global' => $rowData['attribute_scope'],
                    'visible' => $rowData['is_visible'],
                    'searchable' => $rowData['is_searchable'],
                    'filterable' => $rowData['is_filterable'],
                    'comparable' => $rowData['is_comparable'],
                    'visible_on_front' => $rowData['is_visible_on_front'],
                    'visible_in_advanced_search' => $rowData['is_visible_in_advanced_search'],
                    'used_for_price_rules' => $rowData['is_used_for_price_rules'],
                    'filterable_in_search' => $rowData['is_filterable_in_search'],
                    'used_in_product_listing' => $rowData['used_in_product_listing'],
                    'used_for_sort_by' => $rowData['used_for_sort_by'],
                    'position' => $rowData['position'],
                    'wysiwyg_enabled' => $rowData['is_wysiwyg_enabled'],
                    'used_for_promo_rules' => $rowData['is_used_for_promo_rules'],
                    'required_in_admin_store' => $rowData['is_required_in_admin_store'],
                    'used_in_grid' => $rowData['is_used_in_grid'],
                    'visible_in_grid' => $rowData['is_visible_in_grid'],
                    'filterable_in_grid' => $rowData['is_filterable_in_grid'],
                    'search_weight' => $rowData['search_weight'],
                    'option' => $rowData['attribute_options'],
                    'label' => $rowData['label'],
                    'attribute_options' => $rowData['attribute_options'],
                    'storeview_options' => $rowData['storeview_options'],
                    'swatch_options' => $rowData['swatch_options'],
                    'swatch_text_options' => $rowData['swatch_text_options'],
                    'update_product_preview_image' => $rowData['update_product_preview_image'],
                    'use_product_image_for_swatch' => $rowData['use_product_image_for_swatch'],


                    ]
            );
            
          }
        
        $this->moduleDataSetup->getConnection()->endSetup();
        
        
    }

    public function deleteAttribute($attributeCode)
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ($attributeCode as $value) {
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $value);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }
    
}

