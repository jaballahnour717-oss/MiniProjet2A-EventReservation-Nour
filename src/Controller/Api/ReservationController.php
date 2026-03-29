<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/reservations', name: 'api_reservations_')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ReservationRepository  $reservationRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface     $validator,
        private readonly ReservationService     $reservationService,
    ) {}

  
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $event = $this->em->find(Event::class, $data['event_id'] ?? 0);
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Événement introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$event->hasAvailableSeats()) {
            return $this->json([
                'success' => false,
                'message' => 'Plus de places disponibles pour cet événement.',
            ], Response::HTTP_CONFLICT);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setName($data['name'] ?? '');
        $reservation->setEmail($data['email'] ?? '');
        $reservation->setPhone($data['phone'] ?? '');

        $errors = $this->validator->validate($reservation);
        if (count($errors) > 0) {
            $formatted = [];
            foreach ($errors as $error) {
                $formatted[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'errors'  => $formatted,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($reservation);
        $this->em->flush();

        // Envoyer l'email de confirmation
        $this->reservationService->sendConfirmationEmail($reservation);

        return $this->json([
            'success'     => true,
            'message'     => 'Réservation confirmée ! Un email de confirmation vous a été envoyé.',
            'reservation' => $reservation->toArray(),
        ], Response::HTTP_CREATED);
    }

    
    #[Route('/my', name: 'my', methods: ['GET'])]
    public function myReservations(): JsonResponse
    {
        $user = $this->getUser();
        $reservations = $this->reservationRepository->findByEmail($user->getUserIdentifier());

        return $this->json([
            'success' => true,
            'data'    => array_map(fn(Reservation $r) => $r->toArray(), $reservations),
        ]);
    }

    
    #[Route('/event/{id}', name: 'by_event', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function byEvent(Event $event): JsonResponse
    {
        $reservations = $this->reservationRepository->findByEvent($event);

        return $this->json([
            'success' => true,
            'event'   => $event->toArray(),
            'data'    => array_map(fn(Reservation $r) => $r->toArray(), $reservations),
        ]);
    }

    
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Reservation $reservation): JsonResponse
    {
        $this->em->remove($reservation);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Réservation annulée.',
        ]);
    }
}