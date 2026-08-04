# Image Prompt Manager

A web application for creating, organizing, versioning, and evaluating AI image prompts — built as an ICS499 Capstone Project.

## Prompt Library
Browse and manage all saved image prompts. Each prompt displays its title, category, tags, version number, and creation date. Prompts can be edited or deleted directly from the library. Includes search, pagination, and column sorting by title, category, version, and date.

## Create Prompt
Create new image prompts with a title, prompt text, category, and tags. Supports full editing with version tracking — every save logs a new version to the database. Previous versions can be viewed, restored, or deleted from the version history panel.

## Image-to-Prompt
Upload an image and let AI reverse-engineer a descriptive prompt from it. Uses the Cloudflare Llama Vision API (`@cf/meta/llama-3.2-11b-vision-instruct`) to analyze the image and generate a detailed, editable prompt that can be saved directly to the Prompt Library.

> ⚠️ Requires a Cloudflare account and API token with Workers AI permissions. Add your credentials to `includes/config.php` locally — never commit this file.

## Project Course
ICS499 — Software Engineering and Capstone Project
