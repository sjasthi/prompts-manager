# Image Prompt Manager

A web application for creating, organizing, versioning, and evaluating AI image prompts. This project was developed as part of the **ICS499 Software Engineering and Capstone Project**.

---

# Features

## Prompt Library

Browse and manage all saved image prompts. Each prompt displays its title, category, tags, version number, and creation date. Prompts can be edited or deleted directly from the library.

## Create Prompt

Create new image prompts with a title, prompt text, category, and tags. Every time a prompt is edited, a new version is automatically saved to the database. Previous versions can be viewed, restored, or deleted through the version history panel.

## Image Generation

The Image Generation page allows users to generate AI images from prompts stored in the database.

### Features

- Select a saved prompt
- Choose an AI image generation model
- Generate AI images
- Automatically generate a searchable image title
- Download generated images
- Mark images as favorites
- Delete generated images
- Search images by title or AI model
- Filter images by AI model or favorites
- View images in a full-screen gallery
- Navigate between images using previous and next buttons
- Browse image history using pagination for improved performance

### Supported AI Models

- Pollinations AI
- Cloudflare AI (FLUX)

Each generated image stores:

- Prompt used
- Image title
- AI model
- Generation date
- Favorite status
- Image URL

Generated images can be searched, filtered, favorited, downloaded, and deleted directly within the application.

---

# Configuration

Before running the application, edit the following file:

```
includes/config.php
```

Example:

```php
<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');
define('DB_NAME', 'prompt_manager');

define('CF_ACCOUNT_ID', 'YOUR_ACCOUNT_ID');
define('CF_API_TOKEN', 'YOUR_API_TOKEN');
```

Replace each placeholder with your own MySQL and Cloudflare credentials.

---

# Cloudflare AI Setup

Cloudflare AI requires both an **Account ID** and an **API Token**.

## Step 1

Create a free Cloudflare account.

https://dash.cloudflare.com/

---

## Step 2

Create an API Token.

Navigate to:

```
Manage Account
    ↓
Account API Tokens
```

Create a custom API token with permission to use **Workers AI**.

---

## Step 3

Find your Account ID.

Use the Cloudflare dashboard search bar.

Search for:

```
Account ID
```

Copy your Account ID.

---

## Step 4

Open:

```
includes/config.php
```

Replace:

```php
define('CF_ACCOUNT_ID', 'YOUR_ACCOUNT_ID');
define('CF_API_TOKEN', 'YOUR_API_TOKEN');
```

Example:

```php
define('CF_ACCOUNT_ID', '123456789abcdef');
define('CF_API_TOKEN', 'xxxxxxxxxxxxxxxxxxxxxxxx');
```

---

## Step 5

Save the file.

Cloudflare AI image generation is now ready to use.

> **Important:** Never commit your personal Cloudflare API Token or database password to GitHub. Keep `config.php` local or add it to `.gitignore`.

---

# Performance Improvements

To improve scalability as the application grows, several optimizations were implemented:

- Pagination limits the number of images loaded per page.
- Search and filtering are performed directly by MySQL instead of PHP.
- Images are automatically assigned searchable titles.
- Image history is ordered by generation date for efficient retrieval.
- Favorites and image management help users organize large collections of generated images.

---

# Course Information

**Course:** ICS499 – Software Engineering and Capstone Project
