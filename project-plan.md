# Project Plan

## Team

| Member | Feature Area |
|--------|-------------|
| Hieu | Create Prompt, Prompt Versioning, Image-to-Prompt |
| Sitra | Prompt Library, Version History UI, Save Reverse Prompts |
| Lauren | Image Generation, Model Comparison |

---

## FP3 - Planning and Setup

* Finalize requirements.md
* Create project plan
* Design application layout and navigation
* Create repository structure
* Create starter index.php page
* Web site design per the code structure - All the placeholders are in place (when users click a link, you simply get a message "not done")
* Once you have the complete layout of the website, you can then decide the breakup of the workload.

---

## FP4 - Database Design - Final

* Design MySQL schema
* Create prompts table
* Create prompt_versions table
* Create generated_images table
* Test database connectivity
* Load some sample data into the database through PhpMyAdmin
* Ensure that your index.php and other "read-only" pages are fetching the data from this dummy database tables.

---

## FP5 - Prompt Management *(parallel)*

### Hieu
- Build prompt creation form (UI)
- Implement prompt storage in MySQL via PHP
- Implement prompt editing and deletion

### Sitra
- Build prompt library layout (UI)
- Fetch and display all prompts from the database
- Add search or filter to prompt library

### Lauren
- Finalize image generation API selection
- Build image generation interface (UI)
- Wire up API call and display result

---

## FP6 - Prompt Versioning *(parallel)*

### Hieu
- Implement version tracking on prompt save/edit
- Store prompt revisions in `prompt_versions` table
- Build restore previous version functionality

### Sitra
- Build version history UI in the prompt library
- Display list of versions per prompt
- Connect version list to Hieu's restore action

### Lauren
- Store generated image metadata in `generated_images` table
- Display previously generated images per prompt
- Handle API errors and loading states

---

## FP7 - Image Generation *(parallel)*

### Hieu
- Build image upload UI for image-to-prompt reverse engineering
- Integrate image analysis API

### Sitra
- Display reverse-engineered prompt suggestions in the prompt library
- Allow saving image-to-prompt results as new prompts

### Lauren
- Polish image generation interface
- Support generating images from saved prompts
- Display generation history per prompt

---

## FP8 - Model Comparison *(parallel)*

### Hieu
- Display generated prompt suggestions from image analysis
- Refine image-to-prompt UI based on testing

### Sitra
- Save reverse-engineered prompts to the database
- Link image-to-prompt results back to the prompt library

### Lauren
- Support multiple image generation models
- Build side-by-side model comparison UI
- Save and display comparison history

---

## FP9 - Image-to-Prompt Generation *(parallel)*

### Hieu
- End-to-end testing of create prompt and versioning flows
- Fix bugs in prompt creation, editing, and restore

### Sitra
- End-to-end testing of prompt library and version history
- Fix bugs in library display and version UI

### Lauren
- End-to-end testing of image generation and model comparison
- Fix bugs in API integration and comparison history

---

## FP10 - Testing and Finalization *(all three, simultaneously)*

### Hieu
- System testing across all features
- UI improvements for prompt management pages
- Documentation updates for prompt and versioning modules

### Sitra
- System testing across all features
- UI improvements for prompt library pages
- Documentation updates for library and version history modules

### Lauren
- System testing across all features
- UI improvements for image generation pages
- Documentation updates for image generation and comparison modules
- Final presentation preparation (all three collaborate)
