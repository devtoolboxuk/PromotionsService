<?php

include_once "connection.php";

class PromotionsService extends connection
{

    const MARKDOWNS_QUERY = "
        SELECT
            p.id AS promotion_id,
            p.all_products,
            pt.name,
            pp.params
        FROM promotion p
            JOIN promotion_type pt on p.promotion_type_id = pt.id
            LEFT JOIN promotion_param pp on p.id = pp.promotion_id
        WHERE 
            p.start  < NOW()
            AND p.end > NOW()
            AND pt.name in ('PercentOffProductPromotion','AmountOffProductPromotion')
            AND pp.shopper_group_id = :shopper_group_id
        ORDER BY 
            p.all_products DESC";


    protected $parameter = null;
    protected $promotion = [];
    protected $markdown = [];
    protected $shopperGroups = [];

    private function getAllProductsMarkdown($product_ids, $all_products_flag)
    {
        foreach ($product_ids as $product_id) {
            $this->markdown[$product_id] = [
                'type' => $this->promotion[$all_products_flag]['type'],
                'value' => $this->promotion[$all_products_flag]['value']
            ];
        }
    }

    public function getMarkdown()
    {
        return $this->markdown;
    }

    public function getMarkdownPrice($product_id, $price)
    {

        if (isset($this->markdown[$product_id])) {
            $benefitValue = $this->getBenefitValue($this->markdown[$product_id]['type'],
                $this->markdown[$product_id]['value'], $price);
            $price -= $benefitValue;
        }

        return $price;
    }


    function getIndividualProductMarkdown($product_ids)
    {
        $product_ids = implode(',', $product_ids);

        foreach ($this->promotion as $promotion) {

            $query = "SELECT product_id FROM promotion_product WHERE promotion_id = " . $promotion['promotion_id'] . " AND product_id IN (" . $product_ids . ")";
            $statement = $this->db->prepare($query);

            if (!$statement->execute()) {
                throw new RuntimeException(sprintf('Failed to execute query. %s:', 'PRODUCT_MARKDOWNS_QUERY'));
            }

            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if (!isset($this->markdown[$row['product_id']])) {
                    $this->markdown[$row['product_id']] = [
                        'type' => $promotion['type'],
                        'value' => $promotion['value']
                    ];
                }
            }
        }
    }


    function getMarkdownPromotions($product_ids, $shopper_group_id)
    {
        $this->markdown = null;
        if (empty($product_ids)) {
            return [];
        }

        $this->getPromotions($shopper_group_id);

        $all_products_flag = null;

        foreach ($this->promotion as $promotion) {
            if ($promotion['all_products']) {
                $all_products_flag = $promotion['promotion_id'];
            }
        }

        if ($all_products_flag) {
            $this->getAllProductsMarkdown($product_ids, $all_products_flag);
        } else {

            $this->getIndividualProductMarkdown($product_ids);
        }

    }

    /**
     * @param $shopper_group_id
     */
    private function getPromotions($shopper_group_id)
    {

        $this->promotion = [];
        $statement = $this->db->prepare(self::MARKDOWNS_QUERY);
        $statement->bindValue(':shopper_group_id', $shopper_group_id, PDO::PARAM_INT);

        if (!$statement->execute()) {
            throw new RuntimeException(sprintf('Failed to execute query. %s"', "MARKDOWNS_QUERY"));
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $promotion) {

            $type = null;
            switch ($promotion['name']) {
                case 'PercentOffProductPromotion':
                    $type = 'percent';
                    break;
                case 'AmountOffProductPromotion':
                    $type = 'amount';
                    break;
            }

            $this->promotion[$promotion['promotion_id']] = [
                'promotion_id' => $promotion['promotion_id'],
                'all_products' => $promotion['all_products'] == 1 ? true : false,
                'type' => $type,
                'value' => $promotion['params'],
            ];

        }

    }


    function getPromotion()
    {
        return $this->promotion;
    }


    protected function getWasNowPrice($product_id,$rrp)
    {
        $price = $this->getMarkdownPrice($product_id, $rrp);
        return [
            'was'=>$rrp,
            'now'=>$price
        ];

    }

    /**
     * @param $price
     * @return int
     */
    private function priceToInt($price)
    {
        return intval(round($price * 100));
    }

    /**
     * @param string $type
     * @param int $parameter
     * @param float|int $price
     * @return float|int
     */
    public function getBenefitValue($type = 'percent', $parameter, $price)
    {
        $item_benefit_value = $this->priceToInt($price);
        switch ($type) {
            case 'amount':
                $item_benefit_value -= $this->priceToInt($this->getAmountPrice($price, $parameter));
                break;
            case 'percent':
                $item_benefit_value -= $this->priceToInt($this->getPercentPrice($price, $parameter));
                break;
        }

        return $item_benefit_value / 100;
    }

    /**
     * @param float|int $price
     * @param int $parameter
     * @return float
     */
    private function getPercentPrice($price, $parameter)
    {
        return floatval($price - ($price / 100 * $parameter));
    }

    /**
     * @param float|int $price
     * @param int $parameter
     * @return float
     */
    private function getAmountPrice($price, $parameter)
    {
        return floatval($price - $parameter);
    }

}