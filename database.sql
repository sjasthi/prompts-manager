    CREATE DATABASE IF NOT EXISTS prompt_manager;
USE prompt_manager;

CREATE TABLE prompts (
    prompt_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    prompt_text TEXT NOT NULL,
    category VARCHAR(100),
    tags VARCHAR(255),
    favorite_status TINYINT(1) DEFAULT 0,
    status ENUM('active', 'archived') DEFAULT 'active',
    version_number INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE prompt_versions (
    version_id INT AUTO_INCREMENT PRIMARY KEY,
    prompt_id INT NOT NULL,
    version_number INT NOT NULL,
    version_text TEXT NOT NULL,
    modified_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prompt_id) REFERENCES prompts(prompt_id) ON DELETE CASCADE
);

CREATE TABLE generated_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    prompt_id INT NOT NULL,
    model_name VARCHAR(100),
    image_path LONGTEXT,
    generation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    generation_status ENUM('success', 'failure') DEFAULT 'success',
    FOREIGN KEY (prompt_id) REFERENCES prompts(prompt_id) ON DELETE CASCADE
);

CREATE TABLE model_comparisons (
    comparison_id INT AUTO_INCREMENT PRIMARY KEY,
    prompt_id INT NOT NULL,
    models_used VARCHAR(255),
    comparison_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    output_references TEXT,
    FOREIGN KEY (prompt_id) REFERENCES prompts(prompt_id) ON DELETE CASCADE
);

INSERT INTO prompts (title, prompt_text, category, tags, favorite_status) VALUES
('Sunset Over Mountains', 'A breathtaking sunset over snow-capped mountains, golden hour lighting, photorealistic', 'Landscape', 'sunset,mountains,nature', 1),
('Cyberpunk City', 'A neon-lit cyberpunk cityscape at night, rain-soaked streets, blade runner style', 'Architecture', 'cyberpunk,city,neon', 0),
('Portrait of a Wizard', 'An elderly wizard with a long white beard, wearing blue robes, holding a glowing staff, fantasy art style', 'Character', 'wizard,fantasy,portrait', 1);

INSERT INTO prompt_versions (prompt_id, version_number, version_text) VALUES
(1, 1, 'A sunset over mountains'),
(1, 2, 'A breathtaking sunset over snow-capped mountains, golden hour lighting, photorealistic');