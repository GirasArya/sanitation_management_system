# Bionic Cleaning Tracker - Sanitation Management System

The **Bionic Cleaning Tracker** is a comprehensive Sanitation Management System backend built to track, manage, and verify cleaning tasks across various locations. It provides a robust REST API and a management interface powered by **CodeIgniter 4**, **PHP 8.3**, and **PostgreSQL**.

## Features & Roles

The system employs Role-Based Access Control (RBAC) with three primary roles:

| Role | Responsibilities |
|------|-------------|
| **Admin** | Access the analytics dashboard. Manage users, locations, items, and cleaning actions. |
| **Operator** | Perform cleaning tasks, scan locations, submit task completions, and handle revisions. |
| **Verifikator** | Review and verify submitted tasks. Export data and generate summary reports. |

### Key Capabilities
- **Task Tracking:** Operators can log tasks and log visits (e.g. by scanning locations).
- **Verification Workflow:** Verifikators can review submissions and manage verification statuses.
- **Reporting:** Export filtered task logs and generate recapitulation reports to Excel (via PhpSpreadsheet).
- **Profile Management:** Users can manage their personal information, update passwords, and upload profile photos.

## Tech Stack

- **Framework:** CodeIgniter 4
- **Language:** PHP 8.3+
- **Database:** PostgreSQL
- **Authentication:** Session & JWT (`firebase/php-jwt`)
- **Reporting:** PhpSpreadsheet

## Docker Deployment

The fastest way to run this application is with Docker.
See [DOCKER.md](DOCKER.md) for the full guide including how to build, push to Docker Hub, and run on a server.

The repository also includes a GitHub Actions workflow at [.github/workflows/docker-publish.yml](.github/workflows/docker-publish.yml) for publishing tagged builds to Docker Hub.

```bash
cp env .env   # adjust DB_PASS and APP_BASE_URL
docker compose up -d
```

## Local Development (without Docker)

**Requirements:** PHP 8.3+, Composer, PostgreSQL.

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp env .env   # Fill in your database credentials

# 3. Run database migrations and seeders
php spark migrate --all
php spark db:seed MainSeeder

# 4. Start the development server
php spark serve
```
