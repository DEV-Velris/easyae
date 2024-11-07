<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\QuantityType;
use App\Repository\ClientRepository;
use App\Repository\QuantityTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/quantity-type')]

class QuantityTypeController extends AbstractController
{
    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_quantity_type_index', methods: ["GET"])]
    public function getAll(QuantityTypeRepository $quantityTypeRepository, SerializerInterface $serializer,TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllQuantityType";
        $quantityTypeJson = $cache->get($idCache, function (ItemInterface $item) use ($quantityTypeRepository, $serializer) {
            $item->tag("quantityType");
            //$item->tag("product");
            $quantityTypeList = $quantityTypeRepository->findAll();
            $quantityTypeJson = $serializer->serialize($quantityTypeList, 'json', ['groups' => "quantityType"]);
            return $quantityTypeJson;
        });
        return new JsonResponse($quantityTypeJson, JsonResponse::HTTP_OK, [], true);
    }
    #[Route(path: '/{id}', name: 'api_quantity_type_show', methods: ["GET"])]
    public function get(QuantityType $quantityType, SerializerInterface $serializer): JsonResponse
    {
        $quantityTypeJson = $serializer->serialize($quantityType, 'json', ['groups' => "quantityType"]);

        return new JsonResponse($quantityTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_quantity_type_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $quantityType = $serializer->deserialize($request->getContent(), QuantityType::class, 'json', []);
        $quantityType->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;
        $errors = $validator->validate($quantityType);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($quantityType);
        $entityManager->flush();
        $cache->invalidateTags(["quantityType"]);
        $accountJson = $serializer->serialize($quantityType, 'json', ['groups' => "quantityType"]);
        return new JsonResponse($accountJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache, QuantityType $quantityType, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $updatedQuantityType = $serializer->deserialize($request->getContent(), QuantityType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $quantityType]);
        $updatedQuantityType->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($updatedQuantityType);
        $entityManager->flush();
        $cache->invalidateTags(["quantityType"
            //, "product"
        ]);

        $location = $urlGenerator->generate("api_quantity_type_show", ['id' => $updatedQuantityType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_delete', methods: ["DELETE"])]
    public function delete(TagAwareCacheInterface $cache, QuantityType $quantityType, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            if (!$this->isGranted("ROLE_ADMIN")) {
                return new JsonResponse(["error" => "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh"], JsonResponse::HTTP_FORBIDDEN);
            }
            $entityManager->remove($quantityType);
        } else {
            $quantityType
                ->setStatus("off")
                ->setUpdatedBy($this->user->getId())
            ;
            $entityManager->persist($quantityType);
        }
        $cache->invalidateTags(["quantityType"]);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
