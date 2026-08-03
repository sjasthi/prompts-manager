# Code Structure

## Directory Structure

```
project-root/
├── index.php
├── generate.php
├── delete_image.php
├── favorite_image.php
├── database.sql
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── pages/
│   ├── create_prompt.php
│   ├── prompt_library.php
│   ├── compare_models.php
│   └── image_to_prompt.php
├── includes/
│   ├── config.php
│   └── database.php
├── uploads/
├── database/
│   └── schema.sql
└── docs/
    ├── requirements.md
    ├── project-plan.md
    ├── ux-design.md
    └── code-structure.md
```

## Page Descriptions

| File | Owner | Description |
|------|-------|-------------|
| `index.php` | All | Homepage with navigation tiles to all features |
| `pages/create_prompt.php` | Hieu | Create and edit prompts with version tracking |
| `pages/prompt_library.php` | Hieu | Browse, search, sort, and manage saved prompts |
| `pages/image_to_prompt.php` | Hieu/Sitra | Upload image and generate AI prompt via Cloudflare Vision |
| `generate.php` | Lauren | Generate images from prompts using Pollinations AI or Cloudflare FLUX |
| `pages/compare_models.php` | Lauren/Sitra | Compare outputs from multiple AI models |
| `delete_image.php` | Lauren | Delete generated images |
| `favorite_image.php` | Lauren | Favorite generated images |

## Includes

| File | Description |
|------|-------------|
| `includes/database.php` | MySQLi connection via `getConnection()` |
| `includes/config.php` | Database and API credentials — never commit this file |

## Coding Conventions

- Use meaningful variable names
- Use consistent indentation
- Separate PHP, HTML, CSS, and JavaScript where possible
- Comment complex logic
- Follow consistent file naming conventions
- Validate all user input before processing
- Never commit `includes/config.php` — add credentials locally only
