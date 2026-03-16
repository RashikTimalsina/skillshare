# SkillShare - Skill Sharing System

## Overview

Skillshare is a role-based web application designed to connect teachers and learners through skill sharing. The system allows teachers to offer skills or courses, learners to enroll and learn those skills, and administrators to manage the platform.

The platform supports structured workflows for skill management, enrollments, communication, certification, rewards, and activity tracking. It is designed to simulate a realistic learning platform with proper role-based access control and workflow logic.

Built as a full-stack academic system, Skillshare incorporates modular design, role enforcement, and strong database integrity, simulating a real-world learning platform.

## Key Features

### Authentication Systems

- User registration and login
- Role-based access control (Admin, Teacher, Learner)
- Session management
- Secure password handling

### Role-Based Dashboards

#### Admin Dashboard

-Manage users
-Manage skill categories
-Monitor system activities
-Award reward points to teachers
-Issue certificates

#### Teacher Dashboard

- Create and manage skills/courses
- View enrolled learners
- Receive reward points from admin
- Interact with learners through messaging

#### Learner Dashboard

- Browse and search skills
- Enroll in courses
- Provide feedback and ratings
- Receive certificates
- Communicate with teachers

### Technologies Used

#### Frontend

- HTML
- CSS
- JavaScript
- Font Awesome

#### Backend

- PHP

#### Database

-MySQL

#### Environment

-Apache / Local server (XAMPP, MAMP, etc.)

## Installation Guide

#### 1. Clone the Repository

git clone https://github.com/yourusername/SkillSharingSystem.git

#### 2. Move Project to Server Directory

Place the project folder inside your local server directory.
Example:
`Application/XAMPP/htdocs/`

#### 3. Create the Database

Create a MySQL database, for example:
`skillsharing_db`

#### 4. Import the SQL File

Import the provided database SQL file using phpMyAdmin.

#### 5. Configure Database Connection

Update the database configuration file:
`/database/Database.php`
Set your database credentials:
`host: localhost`
`username: root`
`password: `
`database: skillsharing_db`

#### 6. Run the Project

Start your local server and open:

`http://localhost/SkillSharingSystem`
