<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentVisibilityToSelectInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Catalog\Model\Product as MagentoProductModel;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DB\Select;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class JoinParentVisibilityToSelect implements JoinParentVisibilityToSelectInterface
{
    const PARENT_VISIBILITY_DEFAULT_ALIAS = 'at_parent_visibility_default';
    const PARENT_VISIBILITY_ALIAS = 'at_parent_visibility';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var OptionProvider
     */
    private $optionProvider;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var string[]
     */
    private $tableAliases = [
        'parent_visibility_default' => self::PARENT_VISIBILITY_DEFAULT_ALIAS,
        'parent_visibility' => self::PARENT_VISIBILITY_ALIAS,
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeRepositoryInterface $attributeRepository
     * @param OptionProvider $optionProvider
     * @param ResourceConnection $resourceConnection
     * @param array $tableAliases
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeRepositoryInterface $attributeRepository,
        OptionProvider $optionProvider,
        ResourceConnection $resourceConnection,
        array $tableAliases = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->attributeRepository = $attributeRepository;
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
        foreach ($tableAliases as $table => $alias) {
            $this->setTableAlias($table, $alias);
        }
    }

    /**
     * @param Select $select
     * @param int $storeId
     *
     * @return Select
     * @throws NoSuchEntityException
     */
    public function execute(Select $select, $storeId)
    {
        if (!is_int($storeId)) {
            throw new \InvalidArgumentException(sprintf(
                'storeId parameter must be int; %s received in %s',
                is_object($storeId) ? get_class($storeId) : gettype($storeId), // phpcs:ignore
                __METHOD__
            ));
        }

        // Let this exception bubble because if this core attribute is missing, we're in bigger trouble!
        $visibilityAttribute = $this->attributeRepository->get(
            MagentoProductModel::ENTITY,
            MagentoProductModel::VISIBILITY
        );
        $visibilityAttributeTable = $this->resourceConnection->getTableName(
            'catalog_product_entity_' . $visibilityAttribute->getBackendType()
        );
        $connection = $select->getConnection();

        $tableAliases = [
            'parent_visibility_default' => $this->getTableAlias('parent_visibility_default'),
            'parent_visibility' => $this->getTableAlias('parent_visibility'),
            'catalog_product_super_link' => $this->getTableAlias('catalog_product_super_link'),
        ];
        $tableAliasesQuoted = array_map([$connection, 'quoteIdentifier'], $tableAliases);

        $select->joinInner(
            [$tableAliases['parent_visibility_default'] => $visibilityAttributeTable],
            implode(' AND ', [
                sprintf(
                    '%s.%s = %s.parent_id',
                    $tableAliasesQuoted['parent_visibility_default'],
                    $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                    $tableAliasesQuoted['catalog_product_super_link']
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_visibility_default'] . '.attribute_id = ?',
                    (int)$visibilityAttribute->getAttributeId()
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_visibility_default'] . '.store_id = ?',
                    Store::DEFAULT_STORE_ID
                )
            ]),
            []
        );

        $select->joinLeft(
            [$tableAliases['parent_visibility'] => $visibilityAttributeTable],
            implode(' AND ', [
                sprintf(
                    '%s.%s = %s.parent_id',
                    $tableAliasesQuoted['parent_visibility'],
                    $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                    $tableAliasesQuoted['catalog_product_super_link']
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_visibility'] . '.attribute_id = ?',
                    (int)$visibilityAttribute->getAttributeId()
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_visibility'] . '.store_id = ?',
                    $storeId
                )
            ]),
            []
        );

        $excludedVisibilities = [Visibility::VISIBILITY_NOT_VISIBLE];
        if (!$this->scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY,
            ScopeInterface::SCOPE_STORES,
            $storeId
        )) {
            $excludedVisibilities[] = Visibility::VISIBILITY_IN_CATALOG;
        }

        $select->where(
            sprintf(
                '(IF(%s.value_id > 0, %s.value, %s.value)) NOT IN (?)',
                $tableAliases['parent_visibility'],
                $tableAliases['parent_visibility'],
                $tableAliases['parent_visibility_default']
            ),
            $excludedVisibilities
        );

        return $select;
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return void
     */
    public function setTableAlias($table, $alias)
    {
        $table = (string)$table;

        if ($alias) {
            $this->tableAliases[$table] = (string)$alias;
        } else {
            unset($this->tableAliases[$table]);
        }
    }

    /**
     * @param string $table
     *
     * @return string
     */
    public function getTableAlias($table)
    {
        return isset($this->tableAliases[$table])
            ? $this->tableAliases[$table]
            : $table;
    }
}
