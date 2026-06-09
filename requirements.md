# Prompt Manager – Project Requirements

## Introduction

A **Prompt Manager** is a tool used to organize, store, version, and optimize instructions for interacting with Large Language Models (LLMs). This system focuses specifically on **image-based prompts**, enabling users to create, evaluate, and refine prompts that generate or describe images across different AI models. It helps teams and individuals maintain consistency, track changes, and reuse high-quality prompts across various AI applications.

Built using **HTML, CSS, JS, jQuery, and Bootstrap** on the front end, **PHP** on the server, and **MySQL** as the backend database, the Prompt Manager provides a structured and scalable environment for prompt lifecycle management.

---

## Technology Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Front End  | HTML, CSS, JavaScript, jQuery, Bootstrap |
| Server     | PHP                                 |
| Backend    | MySQL                               |

---

## Scope

1. Managing prompts specifically for **images**
2. **Comparative evaluation** of different AI models using the same prompt
3. Given an **image**, generate a corresponding prompt
4. Given a **prompt and model**, generate an image
5. Empower users to **create, save, update, delete, and version** their prompts

---

## Stakeholders & User Roles

| Role             | Status      | Description                                                                 |
|------------------|-------------|-----------------------------------------------------------------------------|
| Admin            | Active      | Full system access — manages users, oversees all prompts, configures models |
| Registered User  | Active      | Can create, save, update, delete, and version prompts; run evaluations      |
| Guest            | Future (v2) | Read-only access; can view prompts but cannot save or generate              |

### Admin
- Manages all user accounts and permissions
- Has access to all prompts across all users
- Configures available AI models for comparison and image generation
- Monitors system usage and maintains data integrity

### Registered User
- Creates, saves, updates, deletes, and versions their own prompts
- Submits prompts to generate images (given a selected model)
- Uploads images to auto-generate a corresponding prompt
- Runs comparative evaluations of the same prompt across different models

### Guest *(planned for future release)*
- Limited browsing access to public or shared prompts
- Cannot create, save, or generate — view only
- Serves as an entry point to encourage account registration

## Functional Requirements

The following functional requirements define the core capabilities the Prompt Manager system shall provide to support image prompt creation, storage, generation, comparison, and management.

---

### FR1: Prompt Management

The system shall provide users with the ability to create, organize, manage, and reuse image-generation prompts.

The system shall allow users to:

* Create new prompts through a web interface
* Save prompts persistently within the database
* Edit and update existing prompts
* Delete prompts from storage
* Search prompts using keywords, categories, or tags
* Categorize prompts using custom tags
* Mark prompts as favorites for easier retrieval
* View previously saved prompts through a prompt library

#### Prompt Data Fields

| Field                  | Description                       |
| ---------------------- | --------------------------------- |
| Prompt ID              | Unique identifier for each prompt |
| Prompt Title           | User-defined name for the prompt  |
| Prompt Text            | Content used for image generation |
| Category               | Grouping or classification        |
| Tags                   | Searchable labels                 |
| Favorite Status        | Indicates saved favorites         |
| Status                 | Active or archived state          |
| Creation Date          | Timestamp of creation             |
| Last Modified Date     | Timestamp of last update          |
| Current Version Number | Latest prompt revision            |

---

### FR2: Prompt Versioning

The system shall support version control functionality for prompt management.

The system shall:

* Automatically create a new version when prompts are modified
* Store historical prompt revisions
* Maintain timestamps for all modifications
* Allow users to view previous prompt versions
* Restore previously saved versions
* Track version numbers sequentially

#### Version Data Fields

| Field                  | Description                         |
| ---------------------- | ----------------------------------- |
| Version ID             | Unique identifier for version entry |
| Prompt ID              | Associated prompt                   |
| Version Number         | Revision identifier                 |
| Version Text           | Stored version content              |
| Modification Timestamp | Time of update                      |

---

### FR3: Image Generation

The system shall provide image generation functionality using supported image generation models.

The system shall allow users to:

* Select from supported image generation models
* Enter or select existing prompts
* Submit prompts for image generation
* View generated image outputs
* Store generated image metadata and outputs for future retrieval

#### Generated Image Data Fields

| Field                      | Description                   |
| -------------------------- | ----------------------------- |
| Generated Image ID         | Unique image identifier       |
| Prompt Used                | Prompt used during generation |
| Selected Model             | Model used for generation     |
| Generation Timestamp       | Time image was created        |
| Generated Image Path / URL | Storage location of image     |
| Generation Status          | Success or failure status     |

---

### FR4: Model Comparison

The system shall support comparative evaluation across supported image generation models.

The system shall:

* Execute identical prompts across a limited set of supported models
* Display generated outputs side-by-side
* Allow users to manually compare generated results
* Record comparison sessions for future review
* Save comparison history for previously tested prompts

#### Comparison Data Fields

| Field             | Description                     |
| ----------------- | ------------------------------- |
| Comparison ID     | Unique comparison identifier    |
| Prompt ID         | Associated prompt               |
| Models Used       | Models included in comparison   |
| Comparison Date   | Date comparison occurred        |
| Output References | References to generated outputs |

---

### FR5: Image-to-Prompt Generation

The system shall support prompt suggestion generation from uploaded images.

The system shall:

* Allow image uploads through the interface
* Validate supported image file formats
* Submit uploaded images to an external service or API for analysis
* Generate suggested prompts from uploaded content
* Allow users to edit generated prompts before saving

#### Supported File Formats

| File Type |
| --------- |
| JPG       |
| PNG       |
| WEBP      |

---

## Non-Functional Requirements

The following non-functional requirements define performance expectations, usability standards, reliability goals, and security requirements for the system.

### Performance Requirements

* Average page load times shall remain below 3 seconds under normal operation
* Search operations shall return results within 2 seconds
* Database queries shall be optimized for prompt retrieval
* The system shall support multiple simultaneous users

### Usability Requirements

* The interface shall be responsive across desktop and mobile devices
* Bootstrap shall provide consistent UI styling
* Navigation shall minimize the number of steps required for common tasks
* Forms shall provide user feedback for invalid input

### Reliability Requirements

* The system shall provide error handling for failed operations
* Database failures shall not corrupt stored prompt data
* Generated content metadata shall remain persistent after creation
* Backup mechanisms shall support data recovery

### Security Requirements

* Input validation shall be performed on all forms
* SQL injection protection shall be implemented
* Passwords shall be hashed before storage
* User sessions shall expire after inactivity
* File uploads shall be validated before processing

---

## Database Requirements

The following tables support prompt storage, image generation tracking, version management, and model comparison.

### prompts Table

| Field           | Description            |
| --------------- | ---------------------- |
| prompt_id       | Primary key identifier |
| title           | Prompt title           |
| prompt_text     | Stored prompt content  |
| category        | Prompt grouping        |
| tags            | Searchable labels      |
| favorite_status | Favorite marker        |
| status          | Active/Archived state  |
| version_number  | Current prompt version |
| created_at      | Creation timestamp     |
| updated_at      | Last update timestamp  |

### prompt_versions Table

| Field          | Description            |
| -------------- | ---------------------- |
| version_id     | Primary key identifier |
| prompt_id      | Foreign key reference  |
| version_number | Revision number        |
| version_text   | Stored prompt version  |
| modified_date  | Modification timestamp |

### generated_images Table

| Field             | Description            |
| ----------------- | ---------------------- |
| image_id          | Primary key identifier |
| prompt_id         | Foreign key reference  |
| model_name        | Model used             |
| image_path        | Stored image location  |
| generation_date   | Generation timestamp   |
| generation_status | Output status          |

### model_comparisons Table

| Field             | Description                 |
| ----------------- | --------------------------- |
| comparison_id     | Primary key identifier      |
| prompt_id         | Foreign key reference       |
| models_used       | Models compared             |
| comparison_date   | Date of comparison          |
| output_references | Generated output references |

---

## Constraints

* PHP shall be used as the backend language
* MySQL shall be used for database management
* Bootstrap shall be utilized for frontend styling
* The system shall operate within a standard web browser environment
* External APIs may be required for image generation features
* Availability of image generation features may depend on third-party API availability and usage limits

---

## Future Enhancements

Potential future improvements include:

* AI prompt suggestions
* Import/export functionality for prompts
* Additional image generation model integrations
* Analytics dashboard
* Enhanced search capabilities
* Expanded user customization features
