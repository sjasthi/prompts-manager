# Image Prompt Manager

A web application for creating, organizing, versioning, and evaluating AI image prompts. This project was developed as part of the ICS499 Software Engineering and Capstone Project.

## Prompt Library
Browse and manage all saved image prompts. Each prompt displays its title, category, tags, version number, and creation date. Prompts can be edited or deleted directly from the library. Includes search, pagination, and column sorting by title, category, version, and date.

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

## Configuration
Before running the application, edit the following file:

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

## Running Locally
From the project's root folder, start the PHP development server with:

```bash
php -d upload_max_filesize=10M -d post_max_size=12M -S localhost:8000
```

---

## Cloudflare AI Setup

Cloudflare AI requires both an **Account ID** and an **API Token**.

### Step 1
Create a free Cloudflare account at https://dash.cloudflare.com/

### Step 2
Create an API Token with permission to use **Workers AI**.

### Step 3
Find your Account ID from the Cloudflare dashboard.

### Step 4
Open `includes/config.php` and replace the placeholders with your credentials.

### Step 5
Save the file. Cloudflare AI is now ready to use.

> **Important:** Never commit your personal Cloudflare API Token or database password to GitHub. Keep `config.php` local or add it to `.gitignore`.

---

## Image to Prompt
Upload an image and let AI reverse-engineer a descriptive prompt from it. Uses the Cloudflare Llama Vision API (`@cf/meta/llama-3.2-11b-vision-instruct`) to analyze the image and generate a detailed, editable prompt that can be saved directly to the Prompt Library.

### How to Test
1. Open: `http://localhost:8000/pages/image_to_prompt.php`
2. Upload a JPG, PNG, or WEBP image up to 10 MB.
3. Click **Generate Prompt**.
4. Review or edit the generated prompt.
5. Click **Save Prompt** to add it to the Prompt Library.

### One-Time Cloudflare Model Agreement
A new Cloudflare account must accept the vision model agreement before using Image to Prompt. Create a temporary `agree.php` file in the project root with the Cloudflare API call sending `"prompt" => "agree"` and run it once in the browser.

---

## Compare Models
Run the same prompt across multiple AI models side by side and evaluate the results.

### How to Test
1. Make sure at least one prompt exists in the Prompt Library.
2. Open: `http://localhost:8000/pages/compare_models.php`
3. Select a saved prompt.
4. Click **Compare Both Models**.
5. Review the Pollinations AI and Cloudflare AI results.
6. Select the preferred model, add optional notes, and save the comparison.

### Supported AI Models
- Pollinations AI
- Cloudflare Workers AI (FLUX)

---

## Performance Improvements
- Pagination limits the number of images loaded per page.
- Search and filtering are performed directly by MySQL instead of PHP.
- Images are automatically assigned searchable titles.
- Image history is ordered by generation date for efficient retrieval.
- Favorites and image management help users organize large collections of generated images.

---

## Course Information
**Course:** ICS499 – Software Engineering and Capstone Project
