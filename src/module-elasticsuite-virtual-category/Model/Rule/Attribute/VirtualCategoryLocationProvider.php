<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\Attribute;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocationProviderInterface;

/**
 * Virtual Category Attribute Location Provider.
 *
 * This provider checks whether a given attribute is used in any
 * Virtual Category rule.
 *
 * Virtual category rules are stored in the `virtual_rule` EAV attribute
 * of catalog categories.
 *
 * Implementation details:
 * - Resolves the `virtual_rule` attribute via EAV
 * - Retrieves its backend table (typically `catalog_category_text`)
 * - Filters rows by:
 *      - attribute_id = virtual_rule attribute ID
 *      - value LIKE '%"attribute":"<attribute_code>"%'
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class VirtualCategoryLocationProvider implements LocationProviderInterface
{
    /**
     * Attribute code storing virtual category rules.
     */
    private const ATTRIBUTE_CODE = 'virtual_rule';

    /**
     * Entity type code for categories.
     */
    private const ENTITY_TYPE = 'catalog_category';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * Constructor.
     *
     * @param ResourceConnection           $resource            Database resource connection.
     * @param AttributeRepositoryInterface $attributeRepository Repository used to retrieve EAV attribute metadata
     *                                                          (attribute ID and backend table for virtual_rule).
     */
    public function __construct(
        ResourceConnection $resource,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->resource = $resource;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Check if attribute is present in any virtual category rule.
     *
     * @param string $attribute Attribute code.
     * @return bool
     */
    public function isPresent(string $attribute): bool
    {
        if ($attribute === '') {
            return false;
        }

        try {
            // Load the virtual_rule attribute metadata.
            $eavAttribute = $this->attributeRepository->get(
                self::ENTITY_TYPE,
                self::ATTRIBUTE_CODE
            );
        } catch (NoSuchEntityException $e) {
            // Attribute not found => cannot be used anywhere.
            return false;
        }

        $backendTable = $eavAttribute->getBackendTable();
        $attributeId  = (int) $eavAttribute->getAttributeId();

        if (!$backendTable || !$attributeId) {
            return false;
        }

        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName($backendTable);

        // Build LIKE pattern.
        $likePattern = '%"attribute":"' . $attribute . '"%';

        $select = $connection->select()
            ->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
            ->where('attribute_id = ?', $attributeId)
            ->where('value LIKE ?', $likePattern)
            ->limit(1);

        $count = (int) $connection->fetchOne($select);

        return $count > 0;
    }
}
