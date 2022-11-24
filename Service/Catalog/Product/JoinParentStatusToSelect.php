<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentStatusToSelectInterface;
use Magento\Catalog\Model\Product as MagentoProductModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;

class JoinParentStatusToSelect implements JoinParentStatusToSelectInterface
{
    const PARENT_STATUS_DEFAULT_ALIAS = 'at_parent_status_default';
    const PARENT_STATUS_ALIAS = 'at_parent_status';

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
     * @var string
     */
    private $status;
    /**
     * @var string[]
     */
    private $tableAliases = [
        'parent_status_default' => self::PARENT_STATUS_DEFAULT_ALIAS,
        'parent_status' => self::PARENT_STATUS_ALIAS,
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeRepositoryInterface $attributeRepository
     * @param OptionProvider $optionProvider
     * @param ResourceConnection $resourceConnection
     * @param string $status
     * @param array $tableAliases
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeRepositoryInterface $attributeRepository,
        OptionProvider $optionProvider,
        ResourceConnection $resourceConnection,
        $status,
        array $tableAliases = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->attributeRepository = $attributeRepository;
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
        $this->status = $status;
        foreach ($tableAliases as $table => $alias) {
            $this->setTableAlias($table, $alias);
        }
    }

    /**
     * @param Select $select
     * @param int $storeId
     *
     * @return Select
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
        $statusAttribute = $this->attributeRepository->get(
            MagentoProductModel::ENTITY,
            MagentoProductModel::STATUS
        );
        $statusAttributeTable = $this->resourceConnection->getTableName(
            'catalog_product_entity_' . $statusAttribute->getBackendType()
        );
        $connection = $select->getConnection();

        $tableAliases = [
            'parent_status_default' => $this->getTableAlias('parent_status_default'),
            'parent_status' => $this->getTableAlias('parent_status'),
            'catalog_product_super_link' => $this->getTableAlias('catalog_product_super_link'),
        ];
        $tableAliasesQuoted = array_map([$connection, 'quoteIdentifier'], $tableAliases);

        $select->joinInner(
            [$tableAliases['parent_status_default'] => $statusAttributeTable],
            implode(' AND ', [
                sprintf(
                    '%s.%s = %s.parent_id',
                    $tableAliasesQuoted['parent_status_default'],
                    $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                    $tableAliasesQuoted['catalog_product_super_link']
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_status_default'] . '.attribute_id = ?',
                    (int)$statusAttribute->getAttributeId()
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_status_default'] . '.store_id = ?',
                    Store::DEFAULT_STORE_ID
                )
            ]),
            []
        );

        $select->joinLeft(
            [$tableAliases['parent_status'] => $statusAttributeTable],
            implode(' AND ', [
                sprintf(
                    '%s.%s = %s.parent_id',
                    $tableAliasesQuoted['parent_status'],
                    $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                    $tableAliasesQuoted['catalog_product_super_link']
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_status'] . '.attribute_id = ?',
                    (int)$statusAttribute->getAttributeId()
                ),
                $connection->quoteInto(
                    $tableAliasesQuoted['parent_status'] . '.store_id = ?',
                    $storeId
                )
            ]),
            []
        );

        $select->where(
            sprintf(
                '(IF(%s.value_id > 0, %s.value, %s.value)) = ?',
                $tableAliases['parent_status'],
                $tableAliases['parent_status'],
                $tableAliases['parent_status_default']
            ),
            $this->status
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
