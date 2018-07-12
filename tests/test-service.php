<?php

namespace Devtoolboxuk\PromotionsService;

use PHPUnit_Framework_TestCase as TestCase;

class Service extends TestCase
{
    protected $promotionsService;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->promotionsService = new \PromotionsService(['site'=>'superdry_com']);

    }


    public function testX()
    {
//        $this->promotionsService->getMarkdownPromotions([63301,69749,67304],2);
//
//        $products = [];
//        foreach ([63301,69749,67304] as $product_id)
//        {
//            $price = $this->promotionsService->getMarkdownPrice($product_id, "34.99");
//            $products[$product_id] = [
//                'was'=>'34.99',
//                'now'=>$price
//            ];
//        }
//
//
//        print_r($products);


        //echo $this->promotionsService->getBenefitValue('amount','34.99');
    }

}
