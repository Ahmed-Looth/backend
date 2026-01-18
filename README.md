Classroom Reservation System (Laravel API)
A containerized backend API for managing internal classroom bookings, users, and approvals. Built with Laravel 11, Sanctum, and SQLite.
🛠 Tech Stack
Framework: Laravel (PHP 8.4)
Database: SQLite
Auth: Laravel Sanctum (Token-based)
Container: Docker & Docker Compose
📋 Prerequisites
Docker Desktop (v4.x+)
Docker Compose (v2+)
🚀 Setup Instructions

1. Clone & Configure Environment
   Clone the repository and set up the environment file.

Bash

git clone <your-repo-url> classroom-reservation
cd classroom-reservation
cp .env.example .env

Ensure your .env contains the following SQLite configuration:

Ini, TOML

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite

2. Prepare Database
   Create the SQLite database file and ensure permissions are correct.

Bash

mkdir -p database
touch database/database.sqlite
chmod -R 775 database

3. Build & Run Containers
   Build the containers and start the application.

Bash

docker compose build --no-cache
docker compose up -d

4. Install Dependencies & Seed Data
   Execute the following commands inside the container to generate keys, migrate, and seed default data.

Bash

# Generate App Key

docker compose exec app php artisan key:generate

# Clear Caches

docker compose exec app php artisan optimize:clear

# Migrate and Seed (Force required for production mode)

docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RoleSeeder --force
docker compose exec app php artisan db:seed --class=SuperUserSeeder --force

5. Start the Server
   To make the API accessible via HTTP:

Bash

docker compose exec app php artisan serve --host=0.0.0.0 --port=8000

Base URL: http://localhost:8000
🔑 Access & Authentication
Constraint: Only internal emails (@polytechnic.edu.mv) are valid for user creation.
Default Superadmin Credentials
Use these credentials to obtain your initial Bearer Token.
Key
Value
Email
superadmin@polytechnic.edu.mv
Password
ChangeMe123!

Request Headers
All API requests must include the following headers:

HTTP

Accept: application/json
Content-Type: application/json
Authorization: Bearer <your-token>

📡 API Endpoints
Authentication
Method
Endpoint
Description
POST
/api/login
Authenticate and retrieve token

Bookings
Method
Endpoint
Description
GET
/api/bookings
List all bookings
POST
/api/bookings
Create a new booking
POST
/api/bookings/{id}/cancel-request
Request cancellation of a booking
POST
/api/bookings/{id}/approve
(Admin) Approve a booking
POST
/api/bookings/{id}/reject
(Admin) Reject a booking

Rooms
Method
Endpoint
Description
GET
/api/rooms
List all rooms
GET
/api/rooms/available
List currently available rooms
POST
/api/rooms
(Admin) Create a new room
PATCH
/api/rooms/{id}/deactivate
(Admin) Deactivate a room

User Management & Logs
Method
Endpoint
Description
GET
/api/users
(Admin) List all users
POST
/api/users
(Admin) Create a new user
PATCH
/api/users/{id}/role
(Admin) Change user role
GET
/api/audit-logs
(Superadmin) View system audit logs
GET
/api/audit-logs/export
(Superadmin) Export logs

❓ Troubleshooting
500 Error / Missing APP_KEY
Run the key generator inside the container:

Bash

docker compose exec app php artisan key:generate

Database Errors
Ensure the database file exists and is writable:

Bash

touch database/database.sqlite
chmod 775 database/database.sqlite

Redirects instead of JSON responses
Ensure your API client (Postman/Thunder Client) is sending the header:
Accept: application/json
Full Reset
To completely rebuild the environment and wipe data:

Bash

docker compose down -v
docker compose build --no-cache
docker compose up -d
