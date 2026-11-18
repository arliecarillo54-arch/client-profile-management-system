# Client Profile Management System

## Project Overview
The Client Profile Management System is a web-based application designed to help administrators manage client information efficiently. It provides an organized way to store, view, search, update, and delete client records.

## Features
- Dashboard with client statistics
- Add new client records
- View client information
- Edit existing client records
- Delete client records
- Search clients by name, email, phone, or company
- Filter clients by Active and Inactive status
- Pagination for client records
- Form validation
- Duplicate email prevention
- Secure database queries using PDO and prepared statements

## Technologies Used
- PHP
- MySQL
- HTML
- CSS
- Bootstrap 5
- XAMPP
- phpMyAdmin

## Database
Database name: `client_management_db`

Table name: `clients`

The clients table stores:
- Client ID
- Full Name
- Email
- Phone
- Address
- Company
- Status
- Date Created

## Installation (Local Setup)
1. Install and open XAMPP.
2. Start Apache and MySQL.
3. Create a database named `client_management_db` in phpMyAdmin.
4. Import your database backup into the database.
5. Place the project folder inside `C:\xampp\htdocs`.
6. Create your private `config.local.php` file with your local database credentials.
7. Open `http://localhost/client_profile_system/` in your browser.

## Database Configuration
The project uses a separate `config.local.php` file for database credentials. This file should remain private and must not be uploaded to GitHub.

## Live Website
https://clientprofilesystem.ifree.page

## Developer
Client Profile Management System — Academic Project
