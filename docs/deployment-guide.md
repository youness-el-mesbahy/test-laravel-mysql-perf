# DigitalOcean Docker Deployment Guide

This guide details the step-by-step instructions to deploy the Laravel performance testing platform onto a DigitalOcean Droplet using Docker and Docker Compose.

---

## 1. Create a DigitalOcean Droplet

1. Log in to the **DigitalOcean Control Panel**.
2. Click **Create** > **Droplets**.
3. Choose the **Marketplace** tab and search/select **Docker** (which automatically installs Docker Engine and Compose on Ubuntu).
4. Select a size (for database performance benchmarking, a Droplet with at least **2 GB RAM and 1 or 2 vCPUs** is recommended).
5. Choose your region and select SSH Keys for authentication.
6. Click **Create Droplet**.

---

## 2. Connect and Prepare the Droplet

SSH into your newly created Droplet using its public IP address:

```bash
ssh root@your_droplet_ip
```

Update system dependencies:

```bash
apt-get update && apt-get upgrade -y
```

---

## 3. Clone the Project Repository

Clone your Laravel performance testing repository and navigate to the project directory:

```bash
git clone https://github.com/youness-el-mesbahy/test-laravel-mysql-perf.git
cd test-laravel-mysql-perf
```

---

## 4. Set Up the Environment Configuration

Create a production `.env` file on the Droplet:

```bash
cp .env.example .env
```

Generate the application encryption key:

```bash
# We can do this locally or in a temporary container, but generating one is important:
openssl rand -base64 32
# Copy this value and update APP_KEY=base64:<value> in the .env file.
```

Edit the `.env` file to match production configurations:

```bash
nano .env
```

Ensure the database credentials match your `docker-compose.prod.yml` variables:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your_droplet_ip

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel_perf
DB_USERNAME=root
DB_PASSWORD=your_secure_password
```

---

## 5. Build and Launch the Containers

Run Docker Compose using the production configuration:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

This command will:
1. Build the production multi-stage PHP-FPM container.
2. Download Nginx, MySQL, and Redis images.
3. Establish private bridging networking between the services.
4. Auto-execute `docker/entrypoint.sh` to run migrations and cache Laravel routes, views, and configurations.

Verify all containers are up and running:

```bash
docker compose -f docker-compose.prod.yml ps
```

---

## 6. Seed the Database (Optional Staging/Testing Setup)

To execute the high-performance database seeder to load 250,000 order items for benchmarks on production:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan db:seed
```

---

## 7. Performance Benchmarking from External Clients

Since MySQL port `3306` is mapped to the host, you can perform query analysis or connect external benchmark clients:

- Test web endpoints via: `http://your_droplet_ip/api/products`
- Connect database clients at: `mysql -h your_droplet_ip -P 3306 -u root -p`
