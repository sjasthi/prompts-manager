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
# Running Locally

From the project’s root folder, start the PHP development server with:

```bash
php -d upload_max_filesize=10M -d post_max_size=12M -S localhost:8000

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

## Image to Prompt

### How to Test

1. Start the application using the command in the Running Locally section.
2. Open:

```text
http://localhost:8000/pages/image_to_prompt.php
```

3. Upload a JPG, PNG, or WEBP image up to 10 MB.
4. Click **Generate Prompt**.
5. Review or edit the generated prompt.
6. Click **Save Prompt** to add it to the Prompt Library.

### One-Time Cloudflare Model Agreement

A new Cloudflare account must accept the vision model agreement before using Image to Prompt. From the project folder, run:

```bash
php -r 'require "includes/config.php"; $url = "https://api.cloudflare.com/client/v4/accounts/" . CF_ACCOUNT_ID . "/ai/run/@cf/meta/llama-3.2-11b-vision-instruct"; $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ["Authorization: Bearer " . CF_API_TOKEN, "Content-Type: application/json"], CURLOPT_POSTFIELDS => json_encode(["prompt" => "agree"])]); $response = curl_exec($ch); echo "HTTP " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL . $response . PHP_EOL;'
```

A response containing **“Thank you for agreeing to this model's terms”** confirms that setup is complete, even if the response displays HTTP 403.

## Testing Model Comparison

1. Make sure at least one prompt exists in the Prompt Library.
2. Open:

```text
http://localhost:8000/pages/compare_models.php
```

3. Select a saved prompt.
4. Click **Compare Both Models**.
5. Review the Pollinations AI and Cloudflare AI results.
6. Select the preferred model, add optional notes, and save the comparison.

### Overview

The AI Model Comparison feature allows users to evaluate how different AI image generation models respond to the same prompt. By generating images from multiple models using identical input, users can compare image quality, creativity, and overall performance side by side.

This feature helps users determine which model produces the best results for a particular prompt and provides a way to document those evaluations for future reference.

### Features

* Select a saved prompt from the prompt library.
* Generate images using multiple AI models with the same prompt.
* Display generated images side by side for easy visual comparison.
* Record the preferred model for each comparison.
* Save evaluation notes describing the strengths or weaknesses of each model's output.
* View previously saved comparisons in the Comparison History page.

### Supported AI Models

* **Pollinations AI**
* **Cloudflare Workers AI (FLUX)**

### Comparison Workflow

1. Select a saved prompt.
2. Click **Compare Both Models**.
3. The application generates an image from each supported AI model.
4. Review the generated images displayed side by side.
5. Select the model that produced the preferred result.
6. Enter optional evaluation notes.
7. Save the evaluation for future reference.

### Saved Information

Each comparison stores:

* The prompt that was used.
* The AI models included in the comparison.
* References to the generated outputs.
* The selected preferred model.
* User evaluation notes.
* The date the comparison was performed.

### Comparison History

The Comparison History page allows users to review previously saved evaluations. Users can revisit past comparisons, review notes, identify the preferred model for each prompt, and track how different AI models perform across multiple prompt types.

### Purpose

This feature provides a structured way to evaluate AI-generated images rather than relying on visual inspection alone. By recording comparison results and user feedback, the application creates a history of model performance that can assist users in selecting the most appropriate AI model for future image generation tasks.


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
