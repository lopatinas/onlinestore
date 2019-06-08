<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractController
{
    /**
     * @return Response
     * @throws \Exception
     */
    public function index(): Response
    {
        $products = $this->container['em']->getRepository(Product::class)->findAll();
        $cart = $this->container['session']->get('cart', []);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
        ]);
    }
}
