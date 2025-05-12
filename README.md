# University CMS

A Content Management System for managing university consortium conferences.

## Project Structure

- **Backend**: Laravel API application
- **Frontend**: Vue 3 application
- **Docker**: Complete containerized environment

## Prerequisites

- Docker and Docker Compose installed
- Docker Desktop running (Windows/Mac)
- Git

## Setup Instructions

1. Clone the repository
   ```bash
   git clone <repository-url>
   cd university-cms
   ```

2. Copy the environment files
   ```bash
   cp .env.example .env
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env
   ```

3. Start the Docker services
   ```bash
   docker-compose up -d
   ```

4. Install Backend Dependencies
   ```bash
   docker-compose exec backend composer install
   docker-compose exec backend php artisan key:generate
   docker-compose exec backend php artisan migrate
   ```

5. Access the application
   - Frontend: http://localhost:8080
   - Backend API: http://localhost/api
   - phpMyAdmin (if installed): http://localhost:8081

## Docker Troubleshooting

If you encounter Docker-related issues:

1. **Docker Desktop not running**
   - Ensure Docker Desktop is running on your system
   - Try restarting Docker Desktop

2. **Port conflicts**
   - Check if any ports are already in use (80, 8080, 3306, 6379)
   - Modify the port mappings in .env file if needed

3. **Database connection issues**
   - Verify the database credentials in .env match
   - Try recreating the containers: `docker-compose down -v && docker-compose up -d`

4. **Permission problems**
   - On Linux/macOS, you might need to fix permissions: `chmod -R 777 storage bootstrap/cache`

5. **Build errors**
   - Check the specific service logs: `docker-compose logs frontend`
   - Rebuild a specific service: `docker-compose build --no-cache frontend`

## Development

- Frontend: Vue 3 with Vite
- Backend: Laravel 10
- Database: MySQL 8.0
- Cache: Redis
