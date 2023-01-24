<?php // phpcs:disable Magento2.Legacy.InstallUpgrade.ObsoleteUpgradeDataScript

namespace Klevu\Search\Setup;

use Klevu\Addtocart\Helper\Data as AddtocartHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Source\ThemeVersion;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute as EntityAttribute;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;
    /**
     * @var EntityType
     */
    private $entityType;
    /**
     * @var EntityAttribute
     */
    private $entityAttribute;

    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ConfigWriterInterface|null $configWriter
     * @param EntityType|null $entityType
     * @param EntityAttribute|null $entityAttribute
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ConfigWriterInterface $configWriter = null,
        EntityType $entityType = null,
        EntityAttribute $entityAttribute = null
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->configWriter = $configWriter ?: ObjectManager::getInstance()->get(ConfigWriterInterface::class);
        $this->entityType = $entityType ?: ObjectManager::getInstance()->get(EntityType::class);
        $this->entityAttribute = $entityAttribute ?: ObjectManager::getInstance()->get(EntityAttribute::class);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $entityType = $this->entityType->loadByCode(Product::ENTITY);
        $entityTypeId = $entityType->getId();
        $attributeCollection = $this->entityAttribute->getCollection();
        if (!$attributeCollection) {
            return;
        }
        $attributeCollection->addFieldToFilter("entity_type_id", $entityTypeId);
        $attributeCollection->addFieldToFilter("attribute_code", RatingAttribute::ATTRIBUTE_CODE);

        if (empty($attributeCollection->getFirstItem()->getData())) {
            $attribute = $attributeCollection->getFirstItem();
            $data = [];
            $data['id'] = null;
            $data['entity_type_id'] = $entityTypeId;
            $data['attribute_code'] = RatingAttribute::ATTRIBUTE_CODE;
            $data['backend_type'] = "varchar";
            $data['frontend_input'] = "text";
            $data['frontend_label'] = 'Rating';
            $data['default_value_text'] = '0';
            $data['is_global'] = '0';
            $data['is_user_defined'] = '1';
            $data['group'] = 'Product Details';
            $attribute->setData($data);
            $attribute->save();

            $resource = $setup;
            $read = $setup->getConnection('core_read');
            $write = $setup->getConnection('core_write');

            $select = $read->select()->from($resource->getTable("eav_attribute_set"), [
                'attribute_set_id'
            ])->where("entity_type_id=?", $entityTypeId);
            $attributeSets = $read->fetchAll($select);

            foreach ($attributeSets as $attributeSet) {
                $attributeSetId = $attributeSet['attribute_set_id'];
                $select = $read->select()->from($resource->getTable("eav_attribute"), [
                    'attribute_id'
                ])->where("entity_type_id=?", $entityTypeId)->where("attribute_code=?", "rating");
                $attribute = $read->fetchRow($select);

                $attributeId = $attribute['attribute_id'];
                $select = $read->select()->from($resource->getTable("eav_attribute_group"), [
                    'attribute_group_id'
                ])->where("attribute_set_id=?", $attributeSetId)->where("attribute_group_code=?", 'product-details');
                $attributeGroup = $read->fetchRow($select);

                $attributeGroupId = $attributeGroup['attribute_group_id'];
                $write->beginTransaction();
                $write->insert($resource->getTable("eav_entity_attribute"), [
                    "entity_type_id" => $entityTypeId,
                    "attribute_set_id" => $attributeSetId,
                    "attribute_group_id" => $attributeGroupId,
                    "attribute_id" => $attributeId,
                    "sort_order" => 5
                ]);
                $write->commit();
            }
        }

        // Ref: KS-8652. Set the theme version to v2 by default for new installations
        // while leaving default value in config.xml as v1 to ensure continuity with
        // existing stores
        $this->configWriter->save(
            ConfigHelper::XML_PATH_THEME_VERSION,
            ThemeVersion::V2,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        // Ref: KS-9465. Set add to cart to enabled for new installations so that stores
        //  on theme v2 have the functionality enabled (output in v2 is based on KMC setting)
        $this->configWriter->save(
            AddtocartHelper::XML_PATH_ADDTOCART_ENABLED,
            1,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }
}
