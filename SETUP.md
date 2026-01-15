# Guide de Configuration - Mini Facebook

## Configuration de la Base de Données

### 1. Créer le fichier .env.local

Créez un fichier `.env.local` à la racine du projet avec le contenu suivant :

**Utilisez cette configuration complète pour .env.local :**
```env
###> symfony/framework-bundle ###
APP_SECRET=4476d43e8d5ac371785dd0af0629dc22
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://root:@127.0.0.1:3306/test1?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
```

**Note:** 
- `root` est le nom d'utilisateur MySQL par défaut de XAMPP
- Le mot de passe est vide (laissez vide après `:`)
- `test1` est le nom de la base de données
- Si votre MySQL utilise un autre port, modifiez `3306`
- Si votre MySQL a un mot de passe, ajoutez-le après `root:` comme `root:VotreMotDePasse@127.0.0.1...`

### 2. Activer l'extension MySQL dans PHP

Vérifiez que l'extension PDO MySQL est activée dans votre `php.ini` :

1. Ouvrez `C:\xampp\php\php.ini`
2. Recherchez la ligne `;extension=pdo_mysql`
3. Supprimez le `;` au début pour activer : `extension=pdo_mysql`
4. Recherchez aussi `;extension=mysqli` et activez-la : `extension=mysqli`
5. Redémarrez Apache dans XAMPP

### 3. Créer la base de données

#### Option A : Via phpMyAdmin (XAMPP)
1. Ouvrez http://localhost/phpmyadmin
2. Cliquez sur "Nouvelle base de données"
3. Nom : `test1`
4. Interclassement : `utf8mb4_general_ci`
5. Cliquez sur "Créer"

#### Option B : Via ligne de commande
```bash
mysql -u root -e "CREATE DATABASE test1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Créer les tables (migrations)

Une fois la base de données créée et l'extension MySQL activée, exécutez :

```bash
# Créer les migrations
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

## Démarrer l'application

1. Assurez-vous que XAMPP (Apache et MySQL) est démarré
2. Dans le terminal, naviguez vers le projet et lancez :
   ```bash
   php -S localhost:8000 -t public
   ```
3. Ouvrez votre navigateur sur : http://localhost:8000

## Fonctionnalités

- ✅ Inscription/Connexion utilisateurs
- ✅ Créer des posts
- ✅ Aimer (like) des posts
- ✅ Commenter des posts
- ✅ Supprimer ses propres posts et commentaires
- ✅ Interface responsive avec Bootstrap

## Première utilisation

1. Allez sur http://localhost:8000/register
2. Créez un compte
3. Connectez-vous
4. Créez votre premier post !
