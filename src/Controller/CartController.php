<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    /**
     * @return Response
     * @throws \Exception
     */
    public function index(): Response
    {
        $cart = $this->container['session']->get('cart', []);
        $products = $this->container['em']->getRepository(Product::class)
            ->findBy(['id' => $cart]);

        return $this->render('cart/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @return Response
     */
    public function add(): Response
    {
        $request = $this->container['request'];
        $productId = $request->get('productId');

        if (null === $productId) {
            return $this->json(['error' => 'Не указан товар'], Response::HTTP_BAD_REQUEST);
        }

        $session = $this->container['session'];
        $cart = $session->get('cart', []);

        if (in_array($productId, $cart)) {
            return $this->json(['error' => 'Товар уже в корзине'], Response::HTTP_BAD_REQUEST);
        }

        $cart[] = $productId;
        $session->set('cart', $cart);

        return $this->json([]);
    }
}
