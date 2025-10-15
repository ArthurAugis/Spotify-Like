@echo off
echo Starting Spotify-Like Docker Environment...
echo.

docker-compose up -d --build

echo.
echo Setting up Spotify-Like project...
echo.

REM Wait for MySQL to be ready
echo Waiting for MySQL to be ready...
timeout /t 15 /nobreak >nul

REM Setup automatique du projet
echo Installing project...
docker-compose exec php bash -c "cd /var/www/html && ls -la"
docker-compose exec php bash -c "cd /var/www/html && composer install --no-interaction --ignore-platform-reqs"
docker-compose exec php bash -c "cd /var/www/html && printf "DATABASE_URL=mysql://spotify_user:spotify_password@mysql:3306/spotify_db\nAPP_ENV=dev\nAPP_SECRET=your-secret-key-here\nDEFAULT_URI=http://localhost:8080\nAPP_DEBUG=true\nMESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0\nMAILER_DSN=null://null\n" > .env"
docker-compose exec php bash -c "cd /var/www/html && chown -R www-data:www-data ."
docker-compose exec php bash -c "cd /var/www/html && chmod -R 775 ."
docker-compose exec php bash -c "cd /var/www/html && mkdir -p var/cache var/log"
docker-compose exec php bash -c "cd /var/www/html && chown -R www-data:www-data var"
docker-compose exec php bash -c "cd /var/www/html && chmod -R 775 var"
docker-compose exec php bash -c "cd /var/www/html && php bin/console cache:clear"

echo Setting up database...
docker-compose exec php bash -c "cd /var/www/html && php bin/console doctrine:database:create --if-not-exists"
docker-compose exec php bash -c "cd /var/www/html && php bin/console doctrine:schema:update --force"
docker-compose exec php bash -c "cd /var/www/html && php bin/console doctrine:migrations:sync-metadata-storage"
docker-compose exec php bash -c "cd /var/www/html && php bin/console doctrine:migrations:version --add --all --no-interaction || echo 'No migrations to mark'"

echo.
echo ================================
echo   SETUP COMPLETE!
echo ================================
echo.
echo Access:
echo - App: http://localhost:8080
echo - Database: http://localhost:8081
echo.
pause
