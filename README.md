# ITicket

Application de gestion de tickets et support technique développée avec Symfony.

## Contexte du projet

ITicket est une application de suivi des demandes de support et des incidents internes ou clients. Elle permet de :

- créer et gérer des tickets
- affecter un ticket à un technicien
- suivre le statut d’un ticket
- ajouter des commentaires de suivi
- gérer plusieurs profils utilisateurs : client, technicien et administrateur
- sécuriser les accès selon les rôles et la propriété des ressources
- centraliser la gestion des utilisateurs et des tickets

Le projet a été conçu pour illustrer une architecture Symfony réaliste avec :

- gestion des utilisateurs et rôles
- système d’authentification
- autorisations via voters
- formulaires de gestion
- contrôleurs sécurisés
- tests fonctionnels

## Stack technique

- PHP 8.2+
- Symfony 7.4
- Doctrine ORM
- Twig
- Symfony Security
- Symfony Forms
- Doctrine Migrations
- PHPUnit
- Stimulus / Turbo / Asset Mapper
- MySQL (ou toute base compatible Doctrine)

## Fonctionnalités principales

- Authentification des utilisateurs
- Tableau de bord selon le rôle
- Création et consultation de tickets
- Système de commentaires sur les tickets
- Gestion des urgences et priorités
- Affectation des tickets à un technicien
- Changement de statut selon les droits
- Contrôle d’accès basé sur les rôles et les permissions
- Gestion des utilisateurs côté administration
- Tests de contrôleurs et de sécurité

## Structure du projet

```text
ITicket/
├── assets/                 # Fichiers front (JS, CSS, Stimulus)
├── config/                 # Configuration Symfony
├── migrations/             # Migrations Doctrine (si activées localement)
├── public/                 # Point d’entrée public du projet
├── src/                    # Code PHP de l’application
├── templates/              # Templates Twig
├── tests/                  # Tests PHPUnit / fonctionnels
├── composer.json           # Dépendances PHP
├── .gitignore              # Fichiers à ignorer dans Git
├── phpunit.dist.xml        # Configuration PHPUnit
├── importmap.php           # Configuration des imports front
├── README.md               # Documentation du projet
└── ...
```

## Prérequis

Avant de lancer le projet, vérifie que tu as installé :

- PHP 8.2 ou supérieur
- Composer
- Symfony CLI (recommandé)
- MySQL ou MariaDB
- Un navigateur moderne

## Installation

1. Cloner le projet :

```bash
git clone <url-du-repository>
cd ITicket
```

2. Installer les dépendances PHP :

```bash
composer install
```

3. Configurer l’environnement :

Copie le fichier d’environnement et renseigne les valeurs de connexion à la base :

```bash
cp .env .env.local
```

Puis complète la variable `DATABASE_URL` dans le fichier local.

Exemple :

```env
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0"
```

4. Créer la base de données :

```bash
php bin/console doctrine:database:create
```

5. Exécuter les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

6. Démarrer le serveur Symfony :

```bash
symfony server:start
```

Ou, si tu n’utilises pas Symfony CLI :

```bash
php -S localhost:8000 -t public
```

## Lancer l’application

Après installation :

- ouvre l’URL affichée par `symfony server:start`
- ou accède à :

```text
http://localhost:8000
```

## Tests

Pour exécuter les tests du projet :

```bash
php bin/phpunit
```

Pour exécuter un test spécifique :

```bash
php bin/phpunit tests/Controller
```

ou :

```bash
php bin/phpunit tests/Controller/AdminUserControllerTest.php
```

## Rôles et accès

Le projet met en place une logique de sécurité par rôles :

- `ROLE_CLIENT`
- `ROLE_TECHNICIEN`
- `ROLE_ADMIN`

Les accès sont contrôlés via les voters Symfony pour empêcher un utilisateur de voir ou modifier des ressources auxquelles il n’a pas le droit.

## Variables d’environnement

Le projet utilise les fichiers `.env` et `.env.local` pour gérer la configuration locale, notamment :

- connexion base de données
- mailer
- paramètres applicatifs

Ces fichiers ne doivent pas être poussés sur GitHub.

## Bonnes pratiques suivies

- fichier `.gitignore` configuré pour exclure les fichiers sensibles et locaux
- base de données et variables d’environnement non versionnées
- tests fonctionnels pour valider le comportement
- contrôle d’accès centralisé par sécurité et permissions
- code organisé par contexte métier et contrôleurs
