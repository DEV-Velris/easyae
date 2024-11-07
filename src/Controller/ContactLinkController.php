<?php

namespace App\Controller;

use App\Entity\ContactLink;
use App\Repository\ContactLinkRepository;
use App\Repository\ContactLinkTypeRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/contact-link')]

class ContactLinkController extends AbstractController
{
    private $user;

    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        Security $security
    )
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_contact_link_index', methods: ["GET"])]
    public function getAll(ContactLinkRepository $contactLinkRepository, SerializerInterface $serializer): JsonResponse
    {
        $idCache = 'getAllContactLinks';
        $contactLinkJson = $this->cache->get($idCache, function (ItemInterface $item) use ($contactLinkRepository, $serializer) {
            $item->tag('contactLink');
            $item->tag('contactLinkType');
            $contactLinkList = $contactLinkRepository->findAll();
            return $serializer->serialize($contactLinkList, 'json', ['groups' => 'contactLink']);
        });

        return new JsonResponse($contactLinkJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_contact_link_show', methods: ["GET"])]
    public function get(ContactLink $contactLink, SerializerInterface $serializer): JsonResponse
    {
        // $contactLinkList = $contactLinkRepository->find($id);

        $contactLinkJson = $serializer->serialize($contactLink, 'json', ['groups' => "contactLink"]);

        return new JsonResponse($contactLinkJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_contact_link_new', methods: ["POST"])]
    public function create(Request $request, ContactLinkTypeRepository $contactLinkTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        $contactLinkType = $contactLinkTypeRepository->find($data["contactLinkType"]);
        $contactLink = $serializer->deserialize($request->getContent(), ContactLink::class, 'json', []);
        $contactLink->setContactLinkType($contactLinkType)
            ->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;
        $entityManager->persist($contactLink);
        $entityManager->flush();
        $this->cache->invalidateTags(['contactLink']);
        $contactLinkJson = $serializer->serialize($contactLink, 'json', ['groups' => "contactLink"]);
        return new JsonResponse($contactLinkJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_contact_link_edit', methods: ["PATCH"])]
    public function update(ContactLink $contactLink, UrlGeneratorInterface $urlGenerator, Request $request, ContactLinkTypeRepository $contactLinkTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        if (isset($data['ContactLinkType'])) {
            $contactLinkType = $contactLinkTypeRepository->find($data["contactLinkType"]);
        }

        $updatedContactLink = $serializer->deserialize($request->getContent(), ContactLink::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $contactLink]);
        $updatedContactLink
            ->setContactLinkType($contactLinkType ?? $updatedContactLink->getContactLinkType())
            ->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($updatedContactLink);
        $entityManager->flush();
        $this->cache->invalidateTags(['contactLink']);
//        $contactLinkJson = $serializer->serialize($updatedContactLink, 'json', ['groups' => "contactLinkType"]);
        $location = $urlGenerator->generate("api_contact_link_show", ['id' => $updatedContactLink->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_contact_link_delete', methods: ["DELETE"])]
    public function delete(ContactLink $contactLink, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            if (!$this->isGranted("ROLE_ADMIN")) {
                return new JsonResponse(["error" => "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh"], JsonResponse::HTTP_FORBIDDEN);
            }
            $entityManager->remove($contactLink);
        } else {
            $contactLink->setStatus("off");
            $contactLink->setUpdatedBy($this->user->getId());
            $entityManager->persist($contactLink);
        }  
        $entityManager->flush();
        $this->cache->invalidateTags(['contactLink']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
