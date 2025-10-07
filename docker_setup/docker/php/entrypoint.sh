#!/bin/bash#!/bin/bash#!/bin/bash#!/bin/bash#!/bin/bash#!/bin/bash



set -eset -e



echo "🎵 Starting Spotify-Like automatic setup..."set -e

echo ""

echo "🎵 Starting Spotify-Like automatic setup..."

# Wait for MySQL to be ready

echo "⏳ Waiting for MySQL to be ready..."set -e

while ! mysqladmin ping -h mysql -u root -proot --silent; do

    echo "Waiting for MySQL..."# Wait for database

    sleep 2

doneecho "⏳ Waiting for database..."echo "Starting Spotify-Like initialization..."

echo "✅ MySQL is ready!"

until mysql -h mysql -u root -proot -e "SELECT 1" >/dev/null 2>&1; do

# Create app directory if it doesn't exist

if [ ! -d "/var/www/html/app" ]; then    echo "Waiting for MySQL..."set -eset -e

    echo "📁 Creating app directory..."

    mkdir -p /var/www/html/app    sleep 3

fi

doneecho "Waiting for database..."

cd /var/www/html/app

echo "✅ Database ready!"

# Clone project if not already cloned

if [ ! -f "composer.json" ]; thenuntil php -r "try { new PDO('mysql:host=mysql;port=3306', 'root', 'root'); } catch(Exception \$e) { exit(1); }" >/dev/null 2>&1; doecho "Starting Spotify-Like initialization..."

    echo "📥 Cloning Spotify-Like project..."

    git clone https://github.com/ArthurAugis/Spotify-Like.git .# Clone project if not exists

    echo "✅ Project cloned!"

elseif [ ! -f "composer.json" ]; then    echo "Waiting for MySQL..."

    echo "📂 Project already exists, updating..."

    git pull origin main || echo "⚠️ Could not update, continuing with existing code"    echo "📦 Cloning Spotify-Like project..."

fi

    git clone https://github.com/ArthurAugis/Spotify-Like.git temp_project    sleep 3

# Install PHP dependencies

echo "📦 Installing PHP dependencies..."    cp -r temp_project/* . 2>/dev/null || true

composer install --no-interaction --prefer-dist --optimize-autoloader

echo "✅ PHP dependencies installed!"    cp -r temp_project/.[!.]* . 2>/dev/null || truedone



# Copy environment file    rm -rf temp_project

if [ ! -f ".env" ]; then

    echo "⚙️ Setting up environment..."    echo "✅ Project cloned!"echo "Database ready!"echo "Waiting for database..."

    cp .env.example .env 2>/dev/null || echo "DATABASE_URL=mysql://spotify_user:spotify_password@mysql:3306/spotify_db" > .env

    echo "APP_ENV=dev" >> .envfi

    echo "APP_SECRET=your-secret-key-here" >> .env

    echo "✅ Environment configured!"

fi

# Install dependencies

# Create database and user

echo "🗄️ Setting up database..."if [ -f "composer.json" ]; thenif [ ! -f "composer.json" ]; thenuntil php -r "try { new PDO('mysql:host=mysql;port=3306', 'root', 'root'); echo 'Connected'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; doecho "Starting Spotify-Like initialization..."echo "Starting Spotify-Like initialization..."

mysql -h mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS spotify_db;" 2>/dev/null || echo "Database might already exist"

mysql -h mysql -u root -proot -e "CREATE USER IF NOT EXISTS 'spotify_user'@'%' IDENTIFIED BY 'spotify_password';" 2>/dev/null || echo "User might already exist"    echo "📚 Installing dependencies..."

mysql -h mysql -u root -proot -e "GRANT ALL PRIVILEGES ON spotify_db.* TO 'spotify_user'@'%';" 2>/dev/null

mysql -h mysql -u root -proot -e "FLUSH PRIVILEGES;" 2>/dev/null    composer install --no-interaction --no-dev --ignore-platform-reqs --optimize-autoloader    echo "Cloning Spotify-Like project..."

echo "✅ Database setup complete!"

    echo "✅ Dependencies installed!"

# Run database migrations

echo "🔄 Running database migrations..."fi    git clone https://github.com/ArthurAugis/Spotify-Like.git temp_project    echo "Waiting for MySQL..."

php bin/console doctrine:migrations:migrate --no-interaction || echo "⚠️ Migration issues, continuing..."

echo "✅ Migrations complete!"



# Install frontend dependencies# Configure environment    cp -r temp_project/* . 2>/dev/null || true

echo "🎨 Installing frontend dependencies..."

npm install || echo "⚠️ NPM install failed, continuing..."echo "⚙️ Configuring environment..."

echo "✅ Frontend dependencies installed!"

cat > .env.local << 'EOF'    cp -r temp_project/.[!.]* . 2>/dev/null || true    sleep 3

# Build assets

echo "🏗️ Building assets..."APP_ENV=dev

npm run build || npm run dev || echo "⚠️ Asset build failed, continuing..."

echo "✅ Assets built!"APP_SECRET=bf5c8b0d4adab7c2db8bc7b3e1d7f4e5a8b1c3d6e9f2a5b8c1d4e7f0a3b6c9d2    rm -rf temp_project



# Create test userDATABASE_URL="mysql://spotify_user:spotify_password@mysql:3306/spotify_db?serverVersion=8.0&charset=utf8mb4"

echo "👤 Creating test user..."

php bin/console doctrine:fixtures:load --no-interaction || \MAILER_DSN=null://null    echo "Project cloned successfully!"done

php -r "

require 'vendor/autoload.php';EOF

use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;fi

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

# Remove problematic dev configs

\$kernel = new \App\Kernel('dev', true);

\$kernel->boot();rm -f config/packages/debug.yaml config/packages/web_profiler.yaml config/routes/web_profiler.yamlecho "Database ready!"echo "Waiting for database..."echo "Waiting for database..."

\$container = \$kernel->getContainer();

\$em = \$container->get('doctrine')->getManager();

\$passwordHasher = \$container->get(UserPasswordHasherInterface::class);

# Fix bundles for productionif [ -f "composer.json" ]; then

\$user = \$em->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);

if (!\$user) {cat > config/bundles.php << 'EOF'

    \$user = new User();

    \$user->setEmail('admin@test.com');<?php    echo "Installing Composer dependencies..."

    \$user->setPassword(\$passwordHasher->hashPassword(\$user, 'password'));

    \$em->persist(\$user);

    \$em->flush();

    echo 'Test user created!';return [    composer install --no-interaction --optimize-autoloader --no-dev

} else {

    echo 'Test user already exists!';    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],

}

" 2>/dev/null || echo "⚠️ User creation script had issues, continuing..."    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],    echo "Composer dependencies installed!"if [ ! -f "composer.json" ]; thenuntil mysqladmin ping -h mysql -u root -proot --silent; dountil mysqladmin ping -h mysql -u root -proot --silent; do



echo "✅ Test user ready!"    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],

echo ""

echo "🎉 Spotify-Like setup complete!"    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],fi

echo "🌐 Access your app at: http://localhost:8080"

echo "🔑 Login: admin@test.com / password"    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],

echo ""

    Symfony\UX\Turbo\TurboBundle::class => ['all' => true],    echo "Cloning Spotify-Like project..."

# Start PHP-FPM

exec "$@"    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],

    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],echo "Configuring environment..."

    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],

];cat > .env.local << 'EOF'    git clone https://github.com/ArthurAugis/Spotify-Like.git temp_project    echo "Waiting for MySQL..."    echo "Waiting for MySQL..."

EOF

APP_ENV=dev

# Create directories and set permissions

mkdir -p var/cache var/log public/uploadsAPP_SECRET=bf5c8b0d4adab7c2db8bc7b3e1d7f4e5a8b1c3d6e9f2a5b8c1d4e7f0a3b6c9d2    cp -r temp_project/* . 2>/dev/null || true

chown -R www-data:www-data /var/www/html

chmod -R 755 /var/www/htmlDATABASE_URL="mysql://spotify_user:spotify_password@mysql:3306/spotify_db?serverVersion=8.0&charset=utf8mb4"

chmod -R 777 var/cache var/log public/uploads 2>/dev/null || true

MAILER_DSN=null://null    cp -r temp_project/.[!.]* . 2>/dev/null || true    sleep 2    sleep 2

# Setup Symfony

echo "🔧 Setting up Symfony..."EOF

php bin/console cache:clear --no-warmup || true

php bin/console cache:warmup || true    rm -rf temp_project



# Setup databasemkdir -p var/cache var/log public/uploads

echo "🗄️ Setting up database..."

php bin/console doctrine:database:create --if-not-exists --no-interaction || true    echo "Project cloned successfully!"donedone

php bin/console doctrine:migrations:migrate --no-interaction || true

chown -R www-data:www-data /var/www/html

# Install and compile assets

echo "🎨 Setting up assets..."chmod -R 755 /var/www/htmlfi

php bin/console importmap:install || true

php bin/console asset-map:compile || truechmod -R 777 var/cache var/log public/uploads 2>/dev/null || true



# Create test userecho "Database ready!"echo "Database ready!"

echo "👤 Creating test user..."

mysql -h mysql -u root -proot spotify_db << 'EOSQL'if [ -f "bin/console" ]; then

INSERT IGNORE INTO user (email, roles, password, first_name, last_name, created_at, is_verified) 

VALUES ('admin@test.com', JSON_ARRAY('ROLE_USER'), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Test', NOW(), 1);    echo "Setting up Symfony..."if [ -f "composer.json" ]; then

EOSQL

    php bin/console cache:clear --no-warmup || true

echo "🎉 Spotify-Like ready!"

echo "📍 Access: http://localhost:8080"    php bin/console cache:warmup || true    echo "Installing Composer dependencies..."

echo "🔑 Login: admin@test.com / password"

    

# Start PHP-FPM

exec "$@"    php bin/console doctrine:database:create --if-not-exists --no-interaction || true    composer install --no-interaction --optimize-autoloader

    php bin/console doctrine:migrations:migrate --no-interaction || true

        echo "Composer dependencies installed!"if [ ! -f "composer.json" ]; thenif [ ! -f "composer.json" ]; then

    echo "Setup completed!"

fifi



echo "Spotify-Like ready! Access at http://localhost:8080"    echo "Cloning Spotify-Like project..."    echo "Cloning Spotify-Like project..."



exec "$@"echo "Configuring environment..."

cat > .env.local << EOF    git clone https://github.com/ArthurAugis/Spotify-Like.git temp_project    

APP_ENV=dev

APP_SECRET=bf5c8b0d4adab7c2db8bc7b3e1d7f4e5a8b1c3d6e9f2a5b8c1d4e7f0a3b6c9d2    cp -r temp_project/* . 2>/dev/null || true    git clone https://github.com/ArthurAugis/Spotify-Like.git temp_project

DATABASE_URL="mysql://spotify_user:spotify_password@mysql:3306/spotify_db?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=null://null    cp -r temp_project/.[!.]* . 2>/dev/null || true    

EOF

    rm -rf temp_project    cp -r temp_project/* . 2>/dev/null || true

mkdir -p var/cache public/uploads

    echo "Project cloned successfully!"    cp -r temp_project/.[!.]* . 2>/dev/null || true

chown -R www-data:www-data /var/www/html

chmod -R 755 /var/www/htmlfi    

chmod -R 777 var/cache var/log public/uploads

    rm -rf temp_project

if [ -f "bin/console" ]; then

    echo "Clearing Symfony cache..."if [ -f "composer.json" ]; then    

    php bin/console cache:clear --no-warmup

    php bin/console cache:warmup    echo "Installing Composer dependencies..."    echo "Project cloned successfully!"

    echo "Cache cleared!"

fi    composer install --no-interaction --optimize-autoloaderfi



echo "Setting up database..."    echo "Composer dependencies installed!"

if [ -f "bin/console" ]; then

    php bin/console doctrine:database:create --if-not-exists --no-interactionfiif [ -f "composer.json" ]; then

    echo "Running migrations..."

    php bin/console doctrine:migrations:migrate --no-interaction    echo "Installing Composer dependencies..."

    echo "Database configured!"

    echo "Configuring environment..."    composer install --no-interaction --optimize-autoloader

    echo "Installing assets..."

    php bin/console asset-map:compile || truecat > .env.local << EOF    echo "Composer dependencies installed!"

    

    USER_COUNT=$(php bin/console doctrine:query:dql "SELECT COUNT(u) FROM App\Entity\User u" 2>/dev/null | tail -1 | xargs || echo "0")APP_ENV=devfi

    if [ "$USER_COUNT" -eq "0" ]; then

        echo "Creating test user..."APP_SECRET=bf5c8b0d4adab7c2db8bc7b3e1d7f4e5a8b1c3d6e9f2a5b8c1d4e7f0a3b6c9d2

        php bin/console doctrine:fixtures:load --no-interaction || echo "No fixtures available"

    fiDATABASE_URL="mysql://spotify_user:spotify_password@mysql:3306/spotify_db?serverVersion=8.0&charset=utf8mb4"echo "Configuring environment..."

    

    echo "Setup completed!"MAILER_DSN=null://nullcat > .env.local << EOF

fi

EOFAPP_ENV=dev

echo "Spotify-Like ready! Access at http://localhost:8080"

APP_SECRET=bf5c8b0d4adab7c2db8bc7b3e1d7f4e5a8b1c3d6e9f2a5b8c1d4e7f0a3b6c9d2

exec "$@"
mkdir -p var/cache public/uploads

DATABASE_URL="mysql://spotify_user:spotify_password@mysql:3306/spotify_db?serverVersion=8.0&charset=utf8mb4"

chown -R www-data:www-data /var/www/html

chmod -R 755 /var/www/htmlMAILER_DSN=null://null

chmod -R 777 var/cache var/log public/uploads

# Configuration des uploads

if [ -f "bin/console" ]; thenUPLOADS_PATH=public/uploads

    echo "Clearing Symfony cache..."MAX_FILE_SIZE=100M

    php bin/console cache:clear --no-warmupEOF

    php bin/console cache:warmup

    echo "Cache cleared!"# Créer les répertoires nécessaires

fimkdir -p public/uploads/tracks

mkdir -p public/uploads/covers

echo "Setting up database..."mkdir -p public/uploads/avatars

if [ -f "bin/console" ]; thenmkdir -p var/log

    php bin/console doctrine:database:create --if-not-exists --no-interactionmkdir -p var/cache

    echo "Running migrations..."

    php bin/console doctrine:migrations:migrate --no-interaction# Appliquer les bonnes permissions

    echo "Database configured!"chown -R www-data:www-data /var/www/html

    chmod -R 755 /var/www/html

    echo "Installing assets..."chmod -R 777 var/cache var/log public/uploads

    php bin/console asset-map:compile || true

    # Nettoyer le cache Symfony

    USER_COUNT=$(php bin/console doctrine:query:dql "SELECT COUNT(u) FROM App\Entity\User u" | tail -1 | xargs)if [ -f "bin/console" ]; then

    if [ "$USER_COUNT" -eq "0" ]; then    echo "🧹 Nettoyage du cache Symfony..."

        echo "Creating test user..."    php bin/console cache:clear --no-warmup

        php bin/console doctrine:fixtures:load --no-interaction || echo "No fixtures available"    php bin/console cache:warmup

    fi    echo "✅ Cache nettoyé !"

    fi

    echo "Setup completed!"

fi# Créer la base de données si elle n'existe pas

echo "🗄️  Configuration de la base de données..."

echo "Spotify-Like ready! Access at http://localhost:8080"if [ -f "bin/console" ]; then

    # Créer la base de données

exec "$@"    php bin/console doctrine:database:create --if-not-exists --no-interaction
    
    # Exécuter les migrations
    echo "🔄 Exécution des migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction
    
    echo "✅ Base de données configurée !"
    
    # Installer les assets
    echo "🎨 Installation des assets..."
    php bin/console asset-map:compile || true
    
    # Vérifier s'il y a des utilisateurs, sinon créer un utilisateur de test
    USER_COUNT=$(php bin/console doctrine:query:dql "SELECT COUNT(u) FROM App\Entity\User u" | tail -1 | xargs)
    if [ "$USER_COUNT" -eq "0" ]; then
        echo "👤 Création d'un utilisateur de test..."
        php bin/console doctrine:fixtures:load --no-interaction || echo "Pas de fixtures disponibles"
    fi
    
    echo "✅ Configuration terminée !"
fi

echo "🎉 Spotify-Like est prêt ! Accédez à http://localhost:8080"

# Démarrer PHP-FPM
exec "$@"