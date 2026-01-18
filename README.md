Classroom Reservation System
A Laravel API for managing classroom bookings, running on Docker with SQLite.

🚀 Quick Setup
1. Clone & Configure
Clone the repo and create the environment file.

Bash

git clone <your-repo-url>
cd classroom-reservation
cp .env.example .env
2. Prepare Database
Create the empty SQLite file and set permissions so the container can write to it.

Bash

touch database/database.sqlite
chmod -R 775 database
3. Start Docker
Build and run the containers in the background.

Bash

docker compose build --no-cache
docker compose up -d
Tip: Run docker ps to confirm the containers are running.

4. Install Dependencies
Install PHP packages inside the container.

Bash

docker compose exec app composer install
5. Initialize Application
Generate the app key, clear caches, and run database migrations.

Bash

docker compose exec app php artisan key:generate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate --force