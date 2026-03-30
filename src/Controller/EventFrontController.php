<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\EventRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EventFrontController extends AbstractController
{
    #[Route('/', name: 'app_events_index')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findUpcoming();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/{id}', name: 'app_events_show')]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/events/{id}/reserve', name: 'app_reserve', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reserve(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        ReservationService $reservationService
    ): Response {
        if (!$event->hasAvailableSeats()) {
            $this->addFlash('error', 'Plus de places disponibles.');
            return $this->redirectToRoute('app_events_show', ['id' => $event->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setName($request->request->get('name'));
        $reservation->setEmail($request->request->get('email'));
        $reservation->setPhone($request->request->get('phone'));

        $em->persist($reservation);
        $em->flush();

        $reservationService->sendConfirmationEmail($reservation);

        return $this->render('reservation/confirm.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}