# TAC-Slot

TAC-Slot est une application de gestion des créneaux de tir libre pour un club.
Elle permet aux adhérents de se connecter via un code reçu par email, de réserver un créneau, de confirmer leur présence (check-in) et de suivre leurs réservations.
Un espace d’administration permet de piloter les adhérents, les créneaux, les réservations et les indicateurs d’activité.

---

## 🚀 Fonctionnalités

- Connexion sans mot de passe via code OTP envoyé par email
- Vérification d’email au premier accès
- Réservation de créneaux avec pré-réservation puis confirmation
- Annulation de réservation selon les règles définies
- Check-in à l’arrivée en séance
- Détection et marquage des no-show
- Génération automatique des créneaux sur une période glissante
- Affichage de l’état de la salle (ouverte/fermée, présents, inscrits)
- Espace “Mes réservations” (du jour, futures, passées)
- Tableau de bord administrateur avec statistiques
- Gestion CRUD des adhérents, créneaux, réservations et journaux d’authentification
- Import CSV des adhérents depuis l’interface admin

---

## 🧰 Stack technique

- **Backend** : Symfony (PHP)
- **Frontend** : Twig
- **Base de données** : MariaDB

---

## ⚙️ Installation

### Prérequis

- PHP >= 8.2
- Composer
- Serveur MariaDB
- Symfony CLI (optionnel mais recommandé)

### Étapes

1. Clonez le projet :
   ```bash
   git clone https://github.com/thomaroger/tac-slot
   cd notes
   ```

2. Copiez le fichier d’environnement :
   ```bash
   cp .env.dist .env
   ```

3. Modifiez le fichier `.env` pour y renseigner :
   - `APP_SECRET`
   - `DATABASE_URL` : en fonction du login, mot de passe, hôte et port de votre serveur MariaDB.
   - `MAILER_DSN` : en fonction de la clé et du token du compte Mailjet
   - `MAILER_FROM_EMAIL`: Mail qui est en reply-to


4. Installez les dépendances PHP :
   ```bash
   composer install
   ```

5. Créez la base de données et lancez les migrations :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. Lancez le serveur de développement :
   ```bash
   symfony server:start
   ```

---

## 🛠️ Configuration

### Variables d’environnement importantes

| Variable       | Description                                                 |
|----------------|-------------------------------------------------------------|
| `APP_SECRET`   | Clé secrète de l’application Symfony                        |
| `DATABASE_URL` | URL de connexion à la base de données                       |
| `MAILER_DSN`   | URL de connexion à l'api Mailjey pour envoyer des mails'    |

---

## 📁 Structure rapide du projet

```
.
├── config/         # Configuration Symfony
├── public/         # Point d’entrée web
├── src/            # Code PHP (contrôleurs, entités, etc.)
├── templates/      # Fichiers Twig
├── migrations/     # Migrations de base de données
├── .env            # Variables d’environnement
└── ...
```

---

## 📸 Captures d'écran


---

## 👤 Auteur

- Thomas Roger 

---

## 📄 Licence

Projet privé – non redistribuable.

---
