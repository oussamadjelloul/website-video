# CDN Integration Test Project

A simple PHP fullstack application for testing CDN integration with user-uploaded content.

## Overview

This project is designed to test CDN integration by creating a simple website that allows users to upload, store, and retrieve media content. The application includes user authentication, post management, and video streaming capabilities.

## Features

- **User Authentication**

  - Registration with email verification
  - Login/logout functionality
  - Password reset

- **Content Management**
  - Post creation with text and image uploads
  - Video uploads and streaming
  - Content listing and filtering

## Tech Stack

- **Frontend**: PHP, HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Authentication**: Custom PHP authentication system
- **Form Handling**: PHP form validation
- **File Storage**: Local (dev)

## Project Structure

```
├── public/             # Public web root
│   ├── index.php       # Entry point
│   ├── assets/         # Static assets (CSS, JS, images)
│   └── uploads/        # User uploaded files (dev only)
├── src/
│   ├── controllers/    # Controller classes
│   │   ├── AuthController.php    # Authentication controllers
│   │   ├── PostController.php    # Post management
│   │   └── VideoController.php   # Video management
│   ├── models/         # Database models
│   │   ├── User.php    # User model
│   │   ├── Post.php    # Post model
│   │   └── Video.php   # Video model
│   ├── views/          # PHP templates
│   │   ├── auth/       # Login/registration pages
│   │   ├── posts/      # Post listing and details
│   │   ├── videos/     # Video listing and player
│   │   └── layouts/    # Layout templates
│   ├── lib/            # Utility functions and services
│   │   ├── Database.php  # Database connection
│   │   ├── Auth.php      # Authentication helpers
│   │   ├── Router.php    # Routing system
│   │   └── CDN.php       # CDN integration
│   └── config/         # Configuration files
├── composer.json       # PHP dependencies
├── .htaccess           # Apache config
└── db/                 # Database migrations and seeds
```

## Database Schema

```sql
-- Users Table
CREATE TABLE users (
  id VARCHAR(36) PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(255),
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Posts Table
CREATE TABLE posts (
  id VARCHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT,
  image_url VARCHAR(255),
  cdn_url VARCHAR(255),
  user_id VARCHAR(36) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Videos Table
CREATE TABLE videos (
  id VARCHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  video_url VARCHAR(255) NOT NULL,
  cdn_url VARCHAR(255),
  thumbnail_url VARCHAR(255),
  duration INT,
  user_id VARCHAR(36) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Setup Instructions

### Prerequisites

- PHP 8.0+
- MySQL database
- Composer
- Apache/Nginx web server

### Installation

1. Clone the repository.

2. Install dependencies:

   ```bash
   composer install
   ```

3. Create a `.env` file with the following variables:

   ```
   # Database
   DB_HOST=localhost
   DB_NAME=cdn_test
   DB_USER=root
   DB_PASS=password

   # Auth
   AUTH_SECRET=your-secret-key
   ```

4. Initialize the database:

   ```bash
   php db/migrate.php
   ```

5. Configure your web server to point to the `public` directory.

6. Access the application at `http://localhost` or your configured domain.

## Deployment

### Docker Deployment

The project includes Docker configuration for easy deployment:

1. Make sure Docker and Docker Compose are installed on your server.

2. Configure environment variables in the `.env` file.

3. Build and start the containers:

   ```bash
   docker-compose up -d
   ```

4. Access the application at `http://localhost:80` or your configured domain.

## API Routes

### Authentication

- `POST /auth/register.php` - Register a new user
- `POST /auth/login.php` - Log in a user
- `GET /auth/logout.php` - Log out a user
- `POST /auth/reset-password.php` - Reset user password

### Posts

- `GET /posts/index.php` - Get all posts
- `GET /posts/view.php?id=:id` - Get a specific post
- `POST /posts/create.php` - Create a new post
- `POST /posts/edit.php?id=:id` - Update a post
- `POST /posts/delete.php?id=:id` - Delete a post

### Videos

- `GET /videos/index.php` - Get all videos
- `GET /videos/view.php?id=:id` - Get a specific video
- `POST /videos/upload.php` - Upload a new video
- `POST /videos/edit.php?id=:id` - Update video information
- `POST /videos/delete.php?id=:id` - Delete a video

## License

This project is licensed under the MIT License - see the LICENSE file for details.
