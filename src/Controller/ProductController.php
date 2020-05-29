<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    const PRODUCTS_PER_PAGE = 3;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(EntityManagerInterface $em, ProductRepository $productRepository)
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/product", name="product_add", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $content = json_decode($request->getContent());
        $product = new Product();
        $product->setName($content->name);
        $product->setPriceAmount($content->priceAmount);
        $product->setPriceCurrency($content->priceCurrency);
        $product->setDateCreated(new \DateTime('now'));
        $this->em->persist($product);
        $this->em->flush();

        return new JsonResponse('added');
    }

    /**
     * @Route("/product/{id}", name="product_remove", methods={"DELETE"})
     * @param int $id
     * @return JsonResponse
     */
    public function remove(int $id)
    {
        $product = $this->productRepository->find($id);
        $this->em->remove($product);
        $this->em->flush();

        return new JsonResponse('removed');
    }

    /**
     * @Route("/product/{id}", name="product_update", methods={"PUT"})
     * @param int $id
     * @return JsonResponse
     */
    public function update(int $id, Request $request)
    {
        $content = json_decode($request->getContent());
        $product = $this->productRepository->find($id);
        foreach ($content as $key => $value) {
            $method = 'set' . ucfirst($key);
            $product->$method($value);
        }
        $product->setDateUpdated(new \DateTime('now'));
        $this->em->flush();

        return new JsonResponse('updated');
    }

    /**
     * @Route("/products/page/{nr}", name="product_update", methods={"GET"})
     * @param int $nr
     * @return JsonResponse
     */
    public function list(int $nr, PaginatorInterface $paginator)
    {
        $queryBuilder = $this->productRepository->createQueryBuilder('p')
            ->addSelect('p.id', 'p.name', 'p.priceAmount', 'p.priceCurrency');
        $pagination = $paginator->paginate($queryBuilder, $nr, self::PRODUCTS_PER_PAGE);
        $items = $pagination->getItems();

        return new JsonResponse($items);
    }
}
