# FarmKnowledge - Agricultural Knowledge Hub and Marketplace

A comprehensive web platform designed to serve as both an educational resource and a marketplace for the agricultural community. The platform combines knowledge sharing, e-commerce functionality, and community features to create a complete ecosystem for farmers and agricultural professionals.

## Features

- **Learning Center**
  - Comprehensive educational content
  - Category-based article system
  - Search functionality
  - Interactive learning resources

- **Marketplace**
  - B2B agricultural product listings
  - Multi-currency support
  - Wishlist functionality
  - Real-time messaging system

- **User Management**
  - Secure authentication
  - User profiles
  - Message center
  - Product management for sellers

## Technologies Used

- PHP 7+
- MySQL/MariaDB
- HTML5/CSS3
- JavaScript
- Server-Sent Events (SSE)

## Requirements

- PHP 7.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependencies)
- XAMPP (recommended for local development)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/agriculture-website.git
   ```

2. Configure your web server to point to the project directory

3. Create a MySQL database and import the provided SQL file:
   ```bash
   mysql -u root -p
   CREATE DATABASE farmknowledge_db;
   exit;
   mysql -u root -p farmknowledge_db < database/farmknowledge_db.sql
   ```

4. Copy `config/db_config.example.php` to `config/db_config.php` and update with your database credentials:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'farmknowledge_db');
   ```

5. Set appropriate permissions:
   ```bash
   chmod 755 -R /path/to/project
   chmod 777 -R /path/to/project/uploads
   ```

## Usage

1. Access the website through your web browser
2. Register a new account or use demo credentials:
   - Username: demo@example.com
   - Password: demo123

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact

Your Name - your.email@example.com
Project Link: https://github.com/yourusername/agriculture-website 