<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Rule\Attribute;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocationProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteAbCampaign\Ui\Component\Optimizer\Listing\FilterCollectionNoCampaign;

/**
 * Catalog Optimizer Location Provider (excluding A/B Campaign optimizers)
 *
 * This provider reuses the same filtering logic as the admin grid
 * to ensure consistency with business rules.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class OptimizerLocationProvider implements LocationProviderInterface
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var FilterCollectionNoCampaign
     */
    private FilterCollectionNoCampaign $noCampaignFilter;

    /**
     * Constructor.
     *
     * @param CollectionFactory          $collectionFactory Optimizer collection factory.
     * @param FilterCollectionNoCampaign $noCampaignFilter  Filter to exclude campaign-linked optimizers.
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        FilterCollectionNoCampaign $noCampaignFilter
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->noCampaignFilter = $noCampaignFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function isPresent(string $attribute): bool
    {
        if ($attribute === '') {
            return false;
        }

        $collection = $this->collectionFactory->create();

        // Apply same logic as UI.
        $this->noCampaignFilter->process($collection);

        // Apply attribute search.
        $collection->addFieldToFilter(
            'rule_condition',
            ['like' => '%"attribute":"' . $attribute . '"%']
        );

        // Lightweight existence check.
        $collection->getSelect()->limit(1);

        return (bool) $collection->getSize();
    }
}
