<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── Admin ──────────────────────────────────────
        $admin = new Admin();
        $admin->setUsername('admin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // ── Utilisateurs ───────────────────────────────
        $users = [];
        $userData = [
            ['alice', 'alice@example.com'],
            ['bob',   'bob@example.com'],
            ['carol', 'carol@example.com'],
        ];

        foreach ($userData as [$username, $email]) {
            $user = new User();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $manager->persist($user);
            $users[] = $user;
        }

        // ── Événements ─────────────────────────────────
        $eventsData = [
            [
                'title'       => 'Conférence Tech & Innovation 2025',
                'description' => 'Rejoignez-nous pour une journée de conférences sur les dernières tendances technologiques : IA, blockchain, cloud et cybersécurité. Des intervenants de renom partageront leur expertise.',
                'date'        => '+15 days',
                'location'    => 'Palais des Congrès, Tunis',
                'seats'       => 200,
            ],
            [
                'title'       => 'Workshop Symfony & API Platform',
                'description' => 'Atelier pratique intensif de 2 jours sur Symfony 7, API Platform et les bonnes pratiques de développement moderne. Niveau intermédiaire requis.',
                'date'        => '+20 days',
                'location'    => 'ISET Charguia, Tunis',
                'seats'       => 30,
            ],
            [
                'title'       => 'Meetup Développeurs Tunisie',
                'description' => 'Rencontre mensuelle de la communauté des développeurs. Présentations, networking et échanges sur les projets open source locaux.',
                'date'        => '+8 days',
                'location'    => 'Le Bled, Lac 2, Tunis',
                'seats'       => 80,
            ],
            [
                'title'       => 'Formation Cybersécurité Avancée',
                'description' => 'Formation intensive sur la sécurité des applications web, pentesting, et réponse aux incidents. Certification préparée à l\'issue de la formation.',
                'date'        => '+30 days',
                'location'    => 'Centre de Formation, La Marsa',
                'seats'       => 20,
            ],
            [
                'title'       => 'Hackathon Smart City',
                'description' => '48 heures pour concevoir des solutions innovantes pour la ville intelligente. Prix pour les 3 meilleures équipes. Inscription par équipes de 4.',
                'date'        => '+45 days',
                'location'    => 'Stade de Rades, Tunis',
                'seats'       => 100,
            ],
        ];

        $events = [];
        foreach ($eventsData as $data) {
            $event = new Event();
            $event->setTitle($data['title']);
            $event->setDescription($data['description']);
            $event->setDate(new \DateTime($data['date']));
            $event->setLocation($data['location']);
            $event->setSeats($data['seats']);
            $manager->persist($event);
            $events[] = $event;
        }

        // ── Réservations (données de test) ─────────────
        $reservationsData = [
            [$events[0], $users[0], 'Alice Dupont',   'alice@example.com',   '+216 71 123 456'],
            [$events[0], $users[1], 'Bob Martin',     'bob@example.com',     '+216 72 234 567'],
            [$events[1], $users[0], 'Alice Dupont',   'alice@example.com',   '+216 71 123 456'],
            [$events[2], $users[2], 'Carol Leblanc',  'carol@example.com',   '+216 73 345 678'],
            [$events[2], $users[1], 'Bob Martin',     'bob@example.com',     '+216 72 234 567'],
            [$events[3], $users[2], 'Carol Leblanc',  'carol@example.com',   '+216 73 345 678'],
        ];

        foreach ($reservationsData as [$event, $user, $name, $email, $phone]) {
            $reservation = new Reservation();
            $reservation->setEvent($event);
            $reservation->setName($name);
            $reservation->setEmail($email);
            $reservation->setPhone($phone);
            $manager->persist($reservation);
        }

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "   Admin   : admin / admin123\n";
        echo "   Users   : alice, bob, carol / password123\n";
        echo "   Events  : " . count($eventsData) . " événements créés\n\n";
    }
}