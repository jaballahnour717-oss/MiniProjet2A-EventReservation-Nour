<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventController extends AbstractController
{
    public function __construct(
        private readonly EventRepository        $eventRepository,
        private readonly ReservationRepository  $reservationRepository,
        private readonly EntityManagerInterface $em,
        private readonly FileUploadService      $fileUploadService,
    ) {}

  
    #[Route('', name: 'dashboard')]
    public function dashboard(): Response
    {
        $totalEvents       = $this->eventRepository->count([]);
        $upcomingEvents    = $this->eventRepository->findUpcoming();
        $totalReservations = $this->reservationRepository->count([]);
        $recentReservations = $this->reservationRepository->findRecent(10);

        return $this->render('admin/dashboard.html.twig', [
            'totalEvents'        => $totalEvents,
            'upcomingEvents'     => $upcomingEvents,
            'totalReservations'  => $totalReservations,
            'recentReservations' => $recentReservations,
        ]);
    }

  
    #[Route('/events', name: 'events_index')]
    public function index(): Response
    {
        $events = $this->eventRepository->findAll();

        return $this->render('admin/event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/new', name: 'events_new')]
    public function new(Request $request): Response
    {
        $event = new Event();
        $form  = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $filename = $this->fileUploadService->upload($imageFile, 'events');
                $event->setImage($filename);
            }

            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_events_index');
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

   
    #[Route('/events/{id}', name: 'events_show')]
    public function show(Event $event): Response
    {
        $reservations = $this->reservationRepository->findByEvent($event);

        return $this->render('admin/event/show.html.twig', [
            'event'        => $event,
            'reservations' => $reservations,
        ]);
    }

   
    #[Route('/events/{id}/edit', name: 'events_edit')]
    public function edit(Event $event, Request $request): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                if ($event->getImage()) {
                    $this->fileUploadService->delete($event->getImage());
                }
                $filename = $this->fileUploadService->upload($imageFile, 'events');
                $event->setImage($filename);
            }

            $this->em->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('admin_events_index');
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
            'form'  => $form->createView(),
        ]);
    }

   
    #[Route('/events/{id}/delete', name: 'events_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            if ($event->getImage()) {
                $this->fileUploadService->delete($event->getImage());
            }
            $this->em->remove($event);
            $this->em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('admin_events_index');
    }

   
    #[Route('/reservations', name: 'reservations_index')]
    public function reservations(): Response
    {
        $events = $this->eventRepository->findAll();

        return $this->render('admin/reservation/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/reservations/event/{id}', name: 'reservations_by_event')]
    public function reservationsByEvent(Event $event): Response
    {
        $reservations = $this->reservationRepository->findByEvent($event);

        return $this->render('admin/reservation/by_event.html.twig', [
            'event'        => $event,
            'reservations' => $reservations,
        ]);
    }
}