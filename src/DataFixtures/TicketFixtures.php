<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketLieu;
use App\Enum\TicketStatus;
use App\Enum\TicketType;
use App\Enum\TicketUrgence;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TicketFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('fixture.client@example.com');
        $user->setFirstName('Client');
        $user->setLastName('Fixture');
        $user->setRoles(['ROLE_CLIENT']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $manager->persist($user);

        $categoriesData = [
            'Réseau',
            'Sécurité',
            'Logiciel',
            'Matériel',
            'Email',
            'Accès',
        ];

        $categories = [];

        foreach ($categoriesData as $titre) {
            $categorie = new Categorie();
            $categorie->setTitre($titre);
            $manager->persist($categorie);
            $categories[] = $categorie;
        }

        $titles = [
            'Connexion impossible',
            'Problème de synchronisation',
            'Installation logiciel',
            'Erreur d’impression',
            'Accès distant bloqué',
            'Mise à jour système',
            'Système lent',
            'Perte de données',
            'Erreur de messagerie',
            'Vérification réseau',
            'Support imprimante',
            'Erreur de licence',
            'Paramétrage VPN',
            'Bug d’application',
            'Problème de bureau',
        ];

        $descriptions = [
            'Le système refuse toute connexion depuis le poste utilisateur. Le réseau semble stable mais l’application ne répond plus.',
            'Les fichiers ne se synchronisent plus entre le serveur et le poste client. La dernière mise à jour semble être la cause.',
            'L’utilisateur demande l’installation d’un outil de gestion pour compléter son activité quotidienne sur le réseau interne.',
            'L’imprimante de bureau affiche des erreurs de communication et n’accepte plus d’ordres d’impression depuis le logiciel.',
            'Après la modification du mot de passe, l’accès distant a été refusé pour plusieurs utilisateurs.',
            'La machine est bloquée sur une mise à jour système et ne permet plus aucune intervention locale.',
            'Le poste utilisateur devient très lent lors de l’ouverture des applications et le temps de réponse est très élevé.',
            'Le client signale une perte de données suite à une erreur lors de la sauvegarde automatique du dossier client.',
            'Les messages électroniques ne s’envoient plus correctement et des erreurs d’authentification apparaissent dans le serveur SMTP.',
            'Une vérification réseau complète est nécessaire pour identifier la source des interruptions intermittentes.',
            'La machine d’impression ne détecte plus les fichiers envoyés depuis les postes clients.',
            'La licence d’accès à l’application de gestion est invalide depuis la dernière activation.',
            'Un accès VPN sécurisé est nécessaire pour l’utilisateur en déplacement afin de reprendre le travail normalement.',
            'Une anomalie dans le logiciel de gestion provoque un crash régulier lors de l’enregistrement des données.',
            'Le poste de travail perd régulièrement la session et le client demande une intervention rapide sur le bureau.',
        ];

        for ($i = 1; $i <= 30; $i++) {
            $ticket = new Ticket();
            $ticket->setUser($user);
            $ticket->setType(TicketType::cases()[random_int(0, count(TicketType::cases()) - 1)]);
            $ticket->setCategorie($categories[random_int(0, count($categories) - 1)]);
            $ticket->setUrgence(TicketUrgence::cases()[random_int(0, count(TicketUrgence::cases()) - 1)]);
            $ticket->setLieu(TicketLieu::cases()[random_int(0, count(TicketLieu::cases()) - 1)]);
            $ticket->setTitre($titles[random_int(0, count($titles) - 1)] . ' #' . $i);
            $ticket->setDescription($descriptions[random_int(0, count($descriptions) - 1)]);
            $ticket->setDateCreation(new \DateTime('-' . random_int(1, 30) . ' days'));
            $ticket->setStatus(TicketStatus::cases()[random_int(0, count(TicketStatus::cases()) - 1)]);

            $manager->persist($ticket);
        }

        $manager->flush();
    }
}
