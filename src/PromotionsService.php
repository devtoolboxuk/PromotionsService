<?php

namespace Devtoolboxuk\PromotionsService;

class PromotionsService
{

    protected $parameter = null;
    protected $promotion = [];

    function __construct(\stdClass $object)
    {
        foreach ($object as $promotion) {

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
     * @param $price
     * @return float|int
     */
    public function getBenefitValue($type = 'percent', $price)
    {
        $item_benefit_value = $this->priceToInt($price);
        switch ($type) {
            case 'amount':
                $item_benefit_value -= $this->priceToInt($this->getAmountPrice($price));
                break;
            case 'percent':
                $item_benefit_value -= $this->priceToInt($this->getPercentPrice($price));
                break;
        }

        return $item_benefit_value / 100;
    }

    /**
     * @param $price
     * @return float
     */
    private function getPercentPrice($price)
    {
        return floatval($price - ($price / 100 * $this->parameter));
    }

    /**
     * @param $price
     * @return float
     */
    private function getAmountPrice($price)
    {
        return floatval($price - $this->parameter);
    }

}