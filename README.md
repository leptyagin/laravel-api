# laravel 13 tinder-clone API

A high-performance, **API-only** Tinder clone starter kit built with **Laravel 13**. This project is designed for developers who want a modern, scalable foundation for dating or social networking applications.

---

- **Database:** PostgreSQL 16 (Main & Test containers)
- **Cache:** Redis Alpine
- **Testing:** Pest & PHPStan (Larastan)
- **Code Quality:** Laravel Pint & Rector
- **Docs:** Scramble (L5-Swagger alternative)

---

## docker setup

```bash
make build    #Build images and start containers
make install  #Install dependencies, generate keys, and run migrations
```

API Access: http://localhost:8085

API Documentation: http://localhost:8085/docs/api

## development commands (makefile)

```bash
make up #Start all services
make down	#Stop all services
make test	#Run full test suite (Lint + Types + Unit)
make lint	#Fix code style (Pint) and apply refactoring (Rector)
make fresh #Reset database and run seeds
make tinker	#Enter Laravel interactive shell
make bash	#Open terminal inside the laravel-app container
```

## Architecture
- **Web Server:** Nginx (Alpine)
- **App Processor:** PHP 8.4-FPM
- **Database:** PostgreSQL 16 (Dedicated Test & Main instances)
- **Cache/Queue:** Redis (Alpine)

## license
This project is open-sourced software licensed under the MIT license.

