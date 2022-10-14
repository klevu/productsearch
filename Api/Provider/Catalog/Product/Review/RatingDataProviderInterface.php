<?php

namespace Klevu\Search\Api\Provider\Catalog\Product\Review;

interface RatingDataProviderInterface
{
    /**
     * Return array of data to be used for to calculate ratings.
     *
     * inject your new data provider using di.xml
     * <type name="Klevu\Search\Service\Catalog\Product\Review\UpdateRating">
     *  <arguments>
     *   <argument name="ratingDataProvider" xsi:type="object">Vendor\Module\Provider\RatingDataProvider</argument>
     *  </arguments>
     * </type>
     *
     * fields can be mapped to klevu data in di.xml
     * <type name="Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper">
     *  <arguments>
     *   <argument name="fieldMapping" xsi:type="array">
     *    <item name="entity_pk_value" xsi:type="string">product_id</item>
     *    <item name="sum" xsi:type="string">total</item>
     *    <item name="count" xsi:type="string">number</item>
     *    <item name="average" xsi:type="string">mean</item>
     *    <item name="store" xsi:type="string">store_id</item>
     *   </argument>
     *  </arguments>
     * </type>
     *
     * @param int $productId
     * @param int|null $storeId
     *
     * @return array
     */
    public function getData($productId, $storeId = null);
}
