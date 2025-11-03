<?php

namespace Packetery\Cart;

use CartCore;
use Packetery\Product\ProductAttributeRepository;

class CartService
{
    /** @var ProductAttributeRepository */
    private $productAttributeRepository;

    public function __construct(ProductAttributeRepository $productAttributeRepository)
    {
        $this->productAttributeRepository = $productAttributeRepository;
    }

    public function isAgeVerificationRequired(CartCore $cart): bool
    {
        $products = $cart->getProducts();
        $isAgeVerificationRequired = false;

        foreach ($products as $product) {
            $productAttributes = $this->productAttributeRepository->findByProductId($product['id_product']);
            if ($productAttributes !== null) {
                $isAgeVerificationRequired = $productAttributes->isForAdults();
                if ($isAgeVerificationRequired) {
                    break;
                }
            }
        }
        return $isAgeVerificationRequired;
    }
}
