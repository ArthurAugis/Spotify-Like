#!/bin/bash
echo "Starting Spotify-Like Docker Environment..."
echo

docker-compose up -d --build

echo
echo "Setting up Spotify-Like project..."
echo

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
sleep 15

# Automatic project setup
echo "Installing project..."
docker-compose exec php bash -c "cd /var/www/html && ls -la"
docker-compose exec php bash -c "cd /var/www/html && rm -rf app"
docker-compose exec php bash -c "cd /var/www/html && mkdir -p app"
docker-compose exec php bash -c "cd /var/www/html/app && git clone https://github.com/ArthurAugis/Spotify-Like.git ."
docker-compose exec php bash -c "cd /var/www/html/app && composer update --no-interaction --ignore-platform-reqs"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'DATABASE_URL=mysql://spotify_user:spotify_password@mysql:3306/spotify_db' > .env"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'APP_ENV=dev' >> .env"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'APP_SECRET=your-secret-key-here' >> .env"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'DEFAULT_URI=http://localhost:8080' >> .env"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'APP_DEBUG=true' >> .env"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0' >> .env"
docker-compose exec php bash -c "cd /var/www/html/app && echo 'MAILER_DSN=null://null' >> .env"
docker-compose exec php bash -c "cd /var/www/html && chown -R www-data:www-data app"
docker-compose exec php bash -c "cd /var/www/html && chmod -R 775 app"
docker-compose exec php bash -c "cd /var/www/html/app && mkdir -p var/cache var/log"
docker-compose exec php bash -c "cd /var/www/html/app && chown -R www-data:www-data var"
docker-compose exec php bash -c "cd /var/www/html/app && chmod -R 775 var"
docker-compose exec php bash -c "cd /var/www/html/app && php bin/console cache:clear"

echo "Setting up database..."
docker-compose exec php bash -c "cd /var/www/html/app && php bin/console doctrine:database:create --if-not-exists"
docker-compose exec php bash -c "cd /var/www/html/app && php bin/console doctrine:schema:update --force"
docker-compose exec php bash -c "cd /var/www/html/app && php bin/console doctrine:migrations:sync-metadata-storage"
docker-compose exec php bash -c "cd /var/www/html/app && php bin/console doctrine:migrations:version --add --all --no-interaction || echo 'No migrations to mark'"

echo
echo "================================"
echo "  SETUP COMPLETE!"
echo "================================"
echo
echo "Access:"
echo "- App: http://localhost:8080"
echo "- Database: http://localhost:8081"
echo

# Make the script pause to show completion (Linux equivalent of pause)
read -p "Press any key to continue..."