<?php

namespace App\Controller;

use App\Entity\ContratType;
use App\Repository\ContratTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/contrat-type')]

class ContratTypeController extends AbstractController
{
    #[Route(name: 'api_contrat_type_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function getAll(ContratTypeRepository $contratTypeRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllContratType";
        $contratTypeJson = $cache->get($idCache, function (ItemInterface $item) use ($contratTypeRepository, $serializer) {
            $item->tag("contratType");
            $contratTypeList = $contratTypeRepository->findAll();
            $contratTypeJson = $serializer->serialize($contratTypeList, 'json', ['groups' => "contratType"]);

            return $contratTypeJson;
        });

        return new JsonResponse($contratTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_contrat_type_show', methods: ["GET"])]
    public function get(ContratType $contratType, SerializerInterface $serializer): JsonResponse
    {
        $contratTypeJson = $serializer->serialize($contratType, 'json', ['groups' => "contratType"]);

        return new JsonResponse($contratTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_contrat_type_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $contratType = $serializer->deserialize($request->getContent(), ContratType::class, 'json', []);
        $contratType->setStatus("on");

        $errors = $validator->validate($contratType);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($contratType);
        $entityManager->flush();

        $cache->invalidateTags(["contratType"]);

        $contratTypeJson = $serializer->serialize($contratType, 'json', ['groups' => "contratType"]);
        return new JsonResponse($contratTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_contrat_type_edit', methods: ["PATCH"])]
    public function update(ValidatorInterface $validator, TagAwareCacheInterface $cache, ContratType $contratType, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $updatedContratType = $serializer->deserialize($request->getContent(), ContratType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $contratType]);
        $updatedContratType
            ->setStatus("on")
        ;

        $errors = $validator->validate($contratType);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($updatedContratType);
        $entityManager->flush();

        $cache->invalidateTags(["contratType"]);

        $location = $urlGenerator->generate("api_contrat_type_show", ['id' => $updatedContratType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_contrat_type_delete', methods: ["DELETE"])]
    public function delete(ValidatorInterface $validator, TagAwareCacheInterface $cache, ContratType $contratType, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($contratType);
        } else {
            $contratType
                ->setStatus("off")
            ;
            $entityManager->persist($contratType);
        }

        $entityManager->flush();

        $cache->invalidateTags(["contratType"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
