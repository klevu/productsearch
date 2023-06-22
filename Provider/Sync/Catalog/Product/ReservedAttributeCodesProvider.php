<?php

namespace Klevu\Search\Provider\Sync\Catalog\Product;

use Klevu\Search\Api\Provider\Sync\ReservedAttributeCodesProviderInterface;
use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;

class ReservedAttributeCodesProvider implements ReservedAttributeCodesProviderInterface
{
    /**
     * @return string[]
     */
    public function execute()
    {
        return array_values(array_unique([
            'rating', // May cause sync issues at receiving side
            'rating_count', // May cause sync issues at receiving side
            RatingAttribute::ATTRIBUTE_CODE, // Handled as special case during sync data generation
            ReviewCountAttribute::ATTRIBUTE_CODE, // Handled as special case during sync data generation,
            "name",
            "sku",
            "description",
            "short_description",
            "image",
            "status",
            "visibility",
            "price",
            "tax_class_id",
            "special_price",
            "special_from_date",
            "special_to_date",
            "created_at",
            "product_type",
            "category_ids",
            "media_gallery",
            "gallery",
            "tier_price",
        ]));
    }
}
