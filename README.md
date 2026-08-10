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
Create `includes/config.local.php` for local credentials. This file is ignored by Git and must never be committed.

Example:

```php
<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');
define('DB_NAME', 'prompt_manager');
define('CF_ACCOUNT_ID', 'YOUR_ACCOUNT_ID');
define('CF_API_TOKEN', 'YOUR_API_TOKEN');
define('OPENROUTER_API_KEY', 'YOUR_OPENROUTER_API_KEY');
```

Replace each placeholder with your own credentials. Production deployments can provide the same names as environment variables instead of using `config.local.php`.

> **Important:** Never add `config.local.php`, database passwords, or API keys to GitHub.

## Database Setup

Create the MySQL database and tables using the included `database.sql` file.

The SQL file can be imported using MySQL Workbench, phpMyAdmin, or the MySQL command line.

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
Add the credentials to `includes/config.local.php`.

### Step 5
Save the file. Cloudflare AI is now ready to use.

Cloudflare Workers AI powers the Cloudflare FLUX option on the Generate and Compare pages.

---

## OpenRouter Setup

OpenRouter powers the Image-to-Prompt feature using Google's Gemma 4 26B multimodal model.

1. Create or sign in to an account at https://openrouter.ai/.
2. Create an API key named **Image Prompt Manager**.
3. Add the key as `OPENROUTER_API_KEY` in `includes/config.local.php`.
4. Keep the key private and never commit it to GitHub.

The Image-to-Prompt feature currently uses the `google/gemma-4-26b-a4b-it:free` model through OpenRouter. This multimodal model can analyze uploaded images and return text descriptions that are converted into reusable AI image prompts.

Because the application uses the free version of the model, response times and availability may vary depending on provider traffic and rate limits.

---

## Image to Prompt

Upload an image and let AI reverse-engineer a descriptive prompt from it. The feature sends the uploaded image to OpenRouter, where Google's Gemma 4 26B multimodal model analyzes the image and generates a detailed text prompt.

The generated prompt includes details such as the subject, setting, composition, lighting, colors, mood, and visual style. Users can review and edit the generated prompt before saving it directly to the Prompt Library.

A loading indicator is displayed while the image is being analyzed because processing time may vary when using the external AI service.

### How to Test

1. Open: `http://localhost:8000/pages/image_to_prompt.php`
2. Upload a JPG, PNG, or WEBP image up to 10 MB.
3. Click **Generate Prompt**.
4. Wait for the AI to analyze the uploaded image.
5. Review or edit the generated prompt.
6. Click **Save Prompt** to add it to the Prompt Library.

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
