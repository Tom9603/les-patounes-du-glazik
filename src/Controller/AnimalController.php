<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Enum\AnimalSex;
use App\Enum\AnimalSpecies;
use App\Repository\AnimalRepository;
use App\Service\ImageResizerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/animaux', name: 'app_animal_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AnimalController extends AbstractController
{
    #[Route('/{id}/profil', name: 'profile', methods: ['GET'])]
    public function profile(Animal $animal): Response
    {
        /** @var \App\Entity\Member $member */
        $member = $this->getUser();
        if ($animal->getOwner() !== $member) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('animal/profile.html.twig', [
            'animal' => $animal,
            'member' => $member,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ImageResizerService $resizer): Response
    {
        /** @var \App\Entity\Member $member */
        $member = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('animal-new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_profile');
            }

            $animal = new Animal();
            $animal->setOwner($member);
            $this->fillAnimalFromRequest($animal, $request);

            $em->persist($animal);
            $em->flush();

            $this->handleAvatarUpload($animal, $request, $resizer);
            $em->flush();

            $this->addFlash('success', $animal->getName() . ' a bien été ajouté.');
            return $this->redirectToRoute('app_animal_profile', ['id' => $animal->getId()]);
        }

        return $this->render('animal/new.html.twig', [
            'member' => $member,
            'species' => AnimalSpecies::cases(),
            'sexes' => AnimalSex::cases(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Animal $animal, Request $request, EntityManagerInterface $em, ImageResizerService $resizer): Response
    {
        /** @var \App\Entity\Member $member */
        $member = $this->getUser();
        if ($animal->getOwner() !== $member) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('animal-edit-' . $animal->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_profile');
            }

            $this->fillAnimalFromRequest($animal, $request);
            $this->handleAvatarUpload($animal, $request, $resizer);
            $em->flush();

            $this->addFlash('success', $animal->getName() . ' a bien été mis à jour.');
            return $this->redirectToRoute('app_animal_profile', ['id' => $animal->getId()]);
        }

        return $this->render('animal/edit.html.twig', [
            'member' => $member,
            'animal' => $animal,
            'species' => AnimalSpecies::cases(),
            'sexes' => AnimalSex::cases(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Animal $animal, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Member $member */
        $member = $this->getUser();
        if ($animal->getOwner() !== $member) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('animal-delete-' . $animal->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $name = $animal->getName();
        $em->remove($animal);
        $em->flush();

        $this->addFlash('success', $name . ' a bien été supprimé.');
        return $this->redirectToRoute('app_profile');
    }

    private function handleAvatarUpload(Animal $animal, Request $request, ImageResizerService $resizer): void
    {
        $file = $request->files->get('avatar');
        if (!$file) return;

        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
        if (!in_array($file->getMimeType(), $allowed)) {
            $this->addFlash('error', 'Format d\'image non supporté.');
            return;
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            $this->addFlash('error', 'L\'image ne doit pas dépasser 10 Mo.');
            return;
        }

        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/animals/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'animal_' . $animal->getId() . '_' . uniqid() . '.jpg';
        if ($resizer->resizeToSquare($file->getPathname(), $dir . $filename, 400)) {
            if ($animal->getAvatarFilename()) {
                @unlink($dir . $animal->getAvatarFilename());
            }
            $animal->setAvatarFilename($filename);
        }
    }

    private function fillAnimalFromRequest(Animal $animal, Request $request): void
    {
        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Le nom est obligatoire.');
        }
        $animal->setName($name);

        $speciesValue = $request->request->get('species');
        $species = AnimalSpecies::tryFrom((string) $speciesValue);
        $animal->setSpecies($species ?? AnimalSpecies::Dog);

        $animal->setBreed($request->request->get('breed') ?: null);

        $birthDateStr = $request->request->get('birthDate');
        if ($birthDateStr) {
            $date = \DateTime::createFromFormat('Y-m-d', $birthDateStr);
            $animal->setBirthDate($date ?: null);
        } else {
            $animal->setBirthDate(null);
        }

        $sexValue = $request->request->get('sex');
        $sex = AnimalSex::tryFrom((string) $sexValue);
        $animal->setSex($sex);

        $animal->setColor($request->request->get('color') ?: null);
        $animal->setMicrochip($request->request->get('microchip') ?: null);
        $animal->setSterilized((bool) $request->request->get('sterilized'));
        $animal->setHealthNotes($request->request->get('healthNotes') ?: null);
    }
}
