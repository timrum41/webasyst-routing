<?php

class shopSmartfiltersPluginProductsCollection extends shopProductsCollection
{
    /**
     * @param shopProductsCollection $collection
     */
    public static function prepareCollection($collection)
    {
        if (!empty($collection->options['skip_smartfilters'])) {
            return;
        }
        $collection->options['skip_smartfilters'] = true;

        $data = waRequest::get();
        $delete = array('page', 'sort', 'order');
        foreach ($delete as $k) {
            if (isset($data[$k])) {
                unset($data[$k]);
            }
        }

        if (empty($data)) {
            return;
        }

        // ⚠️ Убрали проверку по p.count — теперь фильтруем только по SKU

        static $selectable_features;
        if ($selectable_features === null) {
            $codes = array_keys($data);
            $fm = new shopFeatureModel();
            $features = $fm->getByCode($codes);
            $selectable_features = array();
            foreach ($features as $feature) {
                if ($feature['selectable']) {
                    $selectable_features[$feature['code']] = $feature;
                }
            }
        }

        if (empty($selectable_features)) {
            return;
        }



foreach ($selectable_features as $f) {
    if (!empty($data[$f['code']])) {

        // Получаем список выбранных значений для этой характеристики
        // Smart Filters обычно кладёт либо feature_value_id, либо значения, которые маппятся на value_id.
        // Попробуем считать, что это value_id (как в стандартной коллекции):
        $ids_raw = $data[$f['code']];
        if (!is_array($ids_raw)) {
            $ids_raw = array($ids_raw);
        }
        // Оставим только числа
        $value_ids = array_map('intval', $ids_raw);
        $value_ids = array_filter($value_ids);
        if (!$value_ids) {
            // Если не смогли получить числа — пропускаем
            continue;
        }

        // Добавляем EXISTS-условие на SKU с остатком
$sku_only_features = ['f_2', 'size']; // или [2] если по ID

foreach ($selectable_features as $f) {
    if (!empty($data[$f['code']])) {

        $ids_raw = $data[$f['code']];
        if (!is_array($ids_raw)) {
            $ids_raw = array($ids_raw);
        }
        $value_ids = array_map('intval', $ids_raw);
        $value_ids = array_filter($value_ids);
        if (!$value_ids) {
            continue;
        }

        $is_sku_only = in_array($f['code'], $sku_only_features); // или in_array($f['id'], [2])

        if ($is_sku_only) {
            $exists_sql = sprintf(
                'EXISTS (
                    SELECT 1
                    FROM shop_product_features pf
                    JOIN shop_product_skus s ON s.id = pf.sku_id
                    WHERE pf.product_id = p.id
                      AND pf.feature_id = %d
                      AND pf.feature_value_id IN (%s)
                      AND s.count > 0
                )',
                (int)$f['id'],
                implode(',', $value_ids)
            );
        } else {
            $exists_sql = sprintf(
                'EXISTS (
                    SELECT 1
                    FROM shop_product_features pf
                    LEFT JOIN shop_product_skus s ON s.id = pf.sku_id
                    WHERE pf.product_id = p.id
                      AND pf.feature_id = %d
                      AND pf.feature_value_id IN (%s)
                      AND (
                            pf.sku_id IS NULL
                            OR (s.count > 0)
                      )
                )',
                (int)$f['id'],
                implode(',', $value_ids)
            );
        }

        // 🔥 ВАЖНО: добавляем условие ДЛЯ КАЖДОЙ характеристики
        $collection->addWhere($exists_sql);
    }
}



        
    }
}
    }
}