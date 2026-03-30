<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\EventService;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EventRepository        $eventRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface     $validator,
        private readonly FileUploadService      $fileUploadService,
    ) {}

    /**
     * GET /api/events — Liste publique des événements
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $events = $this->eventRepository->findUpcoming();

        return $this->json([
            'success' => true,
            'data'    => array_map(fn(Event $e) => $e->toArray(), $events),
        ]);
    }

    /**
     * GET /api/events/{id} — Détail d'un événement
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data'    => $event->toArray(),
        ]);
    }

    /**
     * POST /api/events — Créer un événement (ROLE_ADMIN only, via API)
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $event = new Event();
        $event->setTitle($data['title'] ?? '');
        $event->setDescription($data['description'] ?? '');
        $event->setDate(new \DateTime($data['date'] ?? 'now'));
        $event->setLocation($data['location'] ?? '');
        $event->setSeats((int) ($data['seats'] ?? 0));

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'errors'  => $this->formatErrors($errors),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($event);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'data'    => $event->toArray(),
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT /api/events/{id} — Modifier un événement
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Event $event, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title']))       $event->setTitle($data['title']);
        if (isset($data['description'])) $event->setDescription($data['description']);
        if (isset($data['date']))        $event->setDate(new \DateTime($data['date']));
        if (isset($data['location']))    $event->setLocation($data['location']);
        if (isset($data['seats']))       $event->setSeats((int) $data['seats']);

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'errors'  => $this->formatErrors($errors),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'data'    => $event->toArray(),
        ]);
    }

    /**
     * DELETE /api/events/{id} — Supprimer un événement
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Event $event): JsonResponse
    {
        // Supprimer l'image si elle existe
        if ($event->getImage()) {
            $this->fileUploadService->delete($event->getImage());
        }

        $this->em->remove($event);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Événement supprimé avec succès.',
        ]);
    }

    private function formatErrors(\Symfony\Component\Validator\ConstraintViolationListInterface $errors): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[$error->getPropertyPath()] = $error->getMessage();
        }
        return $formatted;
    }
}