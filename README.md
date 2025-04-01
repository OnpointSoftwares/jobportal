# Job Portal Web Application

A modern job portal web application built with PHP, MySQL, HTML, CSS, and JavaScript. This platform connects job seekers with recruiters, providing a seamless experience for job searching and recruitment.

## Features

### For Job Seekers
- User registration and authentication
- Create and manage professional profiles
- Search for jobs with advanced filters
- Apply for jobs with a single click
- Save jobs for later viewing
- Track application status
- Receive real-time notifications

### For Recruiters
- Company profile management
- Post and manage job listings
- View and manage job applications
- Shortlist candidates
- Contact applicants directly

## Technical Stack

- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP
- Database: MySQL
- Additional: Font Awesome for icons

## Setup Instructions

1. **Database Setup**
   ```sql
   -- Create and import the database schema
   mysql -u root -p < database/schema.sql
   ```

2. **Configuration**
   - Update database credentials in `config/database.php`
   ```php
   define('DB_HOST', 'your_host');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'jobportal');
   ```

3. **Server Requirements**
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Apache/Nginx web server
   - mod_rewrite enabled (for Apache)

4. **File Permissions**
   - Ensure the `uploads` directory has write permissions
   ```bash
   chmod 755 uploads/
   ```

## Directory Structure

```
jobportal/
├── api/                # API endpoints
├── config/            # Configuration files
├── css/              # Stylesheets
├── database/         # Database schema and migrations
├── images/           # Image assets
├── includes/         # Common PHP includes
├── js/              # JavaScript files
├── uploads/          # User uploaded files
└── README.md        # This file
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for database queries
- Session-based authentication
- Input validation and sanitization
- CSRF protection
- XSS prevention

## Contributing

1. Fork the repository
2. Create a new branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
