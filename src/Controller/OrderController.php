<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends AbstractController
{
    /**
     * @return Response
     * @throws \Exception
     */
    public function create(): Response
    {
        $session = $this->container['session'];
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            return $this->json(['error' => 'В корзине нет товаров'], Response::HTTP_BAD_REQUEST);
        }

        $order = new Order();

        foreach ($cart as $productId) {
            $order->addProduct((int)$productId);
        }

        try {
            $em = $this->container['em'];
            $em->persist($order);
            $em->flush();
            $session->remove('cart');
        } catch (ORMException $e) {
            $this->container['logger']->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->json(['error' => 'Не удалось создать заказ'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['orderId' => $order->getId()]);
    }

    /**
     * @param int $id
     *
     * @return Response
     * @throws \Exception
     */
    public function get(int $id): Response
    {
        try {
            $order = $this->loadOrder($id);
        } catch (NotFoundHttpException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        $products = $this->container['em']->getRepository(Product::class)
            ->findBy(['id' => $order->getProducts()]);

        return $this->render('order/view.html.twig', [
            'order' => $order,
            'products' => $products,
        ]);
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function pay(int $id): Response
    {
        try {
            $order = $this->loadOrder($id);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        if ($order->getStatus() === Order::STATUS_PAID) {
            return $this->json(['error' => 'Заказ уже оплачен'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->container['payment_service']->payOrder()) {
            return $this->json(['error' => 'Платежная система недоступна'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $order->setStatus(Order::STATUS_PAID);
        $this->container['em']->flush();

        return $this->json(['status' => $order->getStatusName()]);
    }

    /**
     * @param int $id
     *
     * @return Order
     */
    protected function loadOrder(int $id): Order
    {
        $em = $this->container['em'];
        /** @var Order $order */
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        return $order;
    }
}
