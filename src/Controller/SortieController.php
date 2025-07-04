<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/sortie')]
class SortieController extends AbstractController
{
    #[Route('/', name: 'sortie_list', methods: ['GET'])]
    public function list(SortieRepository $sortieRepository): Response
    {
        return $this->render('sortie/list.html.twig', [
            'sorties' => $sortieRepository->findAll(),
        ]);
    }

    #[Route('/nouvelle', name: 'sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository ): Response
    {
        $sortie = new Sortie();
        $sortie->setorganisateur($this->getUser());

        $form = $this->createForm(SortieType::class, $sortie);
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Créée']));
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été créée avec succès !');
            return $this->redirectToRoute('sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/{id}/modifier', name: 'sortie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        if ($sortie->getorganisateur() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez pas modifier cette sortie.');
        }

        // TODO: Add form handling

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/{id}/annuler', name: 'sortie_cancel', methods: ['GET', 'POST'])]
    public function cancel(Request $request, Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        if ($sortie->getorganisateur() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez pas annuler cette sortie.');
        }

        // TODO: Add form handling for cancellation reason

        return $this->render('sortie/cancel.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/{id}/inscription', name: 'sortie_register', methods: ['POST'])]
    public function register(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$sortie->getParticipants()->contains($user)) {
            $sortie->addParticipant($user);
            $entityManager->persist($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/{id}/desinscription', name: 'sortie_unregister', methods: ['POST'])]
    public function unregister(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($sortie->getParticipants()->contains($user)) {
            $sortie->removeParticipant($user);
            $entityManager->persist($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sortie_show', ['id' => $sortie->getId()]);
    }
}
