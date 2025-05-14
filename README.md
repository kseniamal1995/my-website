# PHP Web Application

This repository contains a PHP web application running in a Docker environment.

## 🚀 Project Structure

```
.
├── docker/
│   ├── nginx/
│   │   └── conf.d/
│   ├── php/
│   │   └── Dockerfile
│   └── mysql/
│       └── init.d/
├── src/
├── docker-compose.yml
└── Makefile
```

## 🛠 Prerequisites

- Docker
- Docker Compose
- Make (optional, but recommended)

## 🔧 Setup

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. Create environment file:
   ```bash
   cp .env.example .env
   ```

3. Configure your environment variables in `.env`

## 🚀 Running the Application

### Using Make

Start the application:
```bash
make up
```

Stop the application:
```bash
make down
```

View logs:
```bash
make logs
```

### Using Docker Compose directly

Start the application:
```bash
docker-compose up -d
```

Stop the application:
```bash
docker-compose down
```

View logs:
```bash
docker-compose logs -f
```

## 📦 Services

The application consists of the following services:

- **Web Server (Nginx)**: Handles HTTP requests and serves static files
- **PHP-FPM**: Processes PHP files
- **MySQL**: Database server
- **PHPMyAdmin**: Database management interface

## 🔒 Environment Variables

The application uses environment variables for configuration. Key variables include:

- Database settings
- Web server configuration
- PHP settings
- PHPMyAdmin configuration
- Application-specific settings

Refer to `.env.example` for all available configuration options.

## 🛠 Development

The source code is mounted in the PHP container at `/var/www/html`. Any changes made to the source files will be immediately reflected in the running application.

## 📝 Logs

Logs can be accessed using:
```bash
# All services
make logs

# Specific service
docker-compose logs -f [service-name]
```

## 🔧 Maintenance

To rebuild containers after Dockerfile changes:
```bash
make build
# or
docker-compose build
```

To restart services:
```bash
make restart
# or
docker-compose restart
``` 