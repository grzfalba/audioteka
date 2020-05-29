<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    const MAX_PRODUCTS = 3;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var CartRepository
     */
    private $cartRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        ProductRepository $productRepository
    ) {
        $this->em = $em;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/cart", name="cart_create", methods={"POST"})
     * @return JsonResponse
     */
    public function create()
    {
        $cart = new Cart();
        $cart->setDateCreated(new \DateTime('now'));
        $this->em->persist($cart);
        $this->em->flush();
        $id = $cart->getId();

        return new JsonResponse(['created', $id]);
    }

    /**
     * @Route("/cart/{cartId}/product/{productId}", name="cart_add_product", methods={"PUT"})
     * @param int $cartId
     * @param int $productId
     * @return JsonResponse
     */
    public function addProduct(int $cartId, int $productId)
    {
        $cart = $this->cartRepository->find($cartId);
        if ($cart->countProduct() < 3) {
            $product = $this->productRepository->find($productId);
            $cart->addProduct($product);
            $cart->setDateUpdated(new \DateTime('now'));
            $this->em->flush();
            return new JsonResponse('added');
        } else {
            return new JsonResponse('cart contain of 3 product');
        }
    }

    /**
     * @Route("/cart/{cartId}/product/{productId}", name="cart_remove_product", methods={"DELETE"})
     * @param int $cartId
     * @param int $productId
     * @return JsonResponse
     */
    public function removeProduct(int $cartId, int $productId)
    {
        $cart = $this->cartRepository->find($cartId);
        $product = $this->productRepository->find($productId);
        $cart->removeProduct($product);
        $this->em->flush();

        return new JsonResponse('removed');
    }

    /**
     * @Route("/cart/{cartId}/products", name="cart_products", methods={"GET"})
     * @param int $cartId
     * @return JsonResponse
     */
    public function listProducts(int $cartId)
    {
        $cart = $this->cartRepository->find($cartId);
        $totalPrice = 0;
        $products = [];
        /** @var Product $product */
        foreach ($cart->getProduct() as $product) {
            $totalPrice += $product->getPriceAmount();
            $products[] = [
                'name' => $product->getName(),
                'priceAmount' => $product->getPriceAmount(),
                'priceCurrency' => $product->getPriceCurrency()
            ];
        }

        return new JsonResponse(['totalPrice' => $totalPrice, 'products' => $products]);
    }
}
