CREATE DATABASE IF NOT EXISTS forms
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE forms;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS forms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  requires_code BOOLEAN NOT NULL,
  code CHAR(5),
  owner_id INT NOT NULL,

  CONSTRAINT fk_forms_owner
    FOREIGN KEY (owner_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT NOT NULL,
  question_text VARCHAR(500) NOT NULL,
  question_type ENUM('OPEN','SINGLE_CHOICE','MULTI_CHOICE') NOT NULL,
  question_order INT NOT NULL,

  CONSTRAINT fk_questions_form
    FOREIGN KEY (form_id) REFERENCES forms(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  UNIQUE KEY uq_form_order (form_id, question_order)
);

CREATE TABLE IF NOT EXISTS question_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  option_text VARCHAR(255) NOT NULL,
  option_order INT NOT NULL,

  CONSTRAINT fk_options_question
    FOREIGN KEY (question_id) REFERENCES questions(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  UNIQUE KEY uq_question_option_order (question_id, option_order)
);

CREATE TABLE IF NOT EXISTS forms_filled(
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS responses(
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_filled_id INT NOT NULL,
    FOREIGN KEY (form_filled_id) REFERENCES forms_filled(id) ON DELETE CASCADE,
    question_id INT NOT NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    response_text VARCHAR(255) NOT NULL
);

# -- Helpful for queries like "get questions for form ordered"
# CREATE INDEX idx_questions_form_order ON questions(form_id, question_order);
# CREATE TABLE IF NOT EXISTS question_options (
#   id INT AUTO_INCREMENT PRIMARY KEY,
#   question_id INT NOT NULL,
#   option_text VARCHAR(255) NOT NULL,
#   option_order INT NOT NULL,
#
#   CONSTRAINT fk_options_question
#     FOREIGN KEY (question_id) REFERENCES questions(id)
#     ON UPDATE CASCADE
#     ON DELETE CASCADE,
#
#   UNIQUE KEY uq_question_option_order (question_id, option_order)
# );
#
# CREATE INDEX idx_options_question_order ON question_options(question_id, option_order);
