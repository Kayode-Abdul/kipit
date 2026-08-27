<div align="center">
  <h1>Kipit POS</h1>

  <p> Kipit is a Point of Sale (POS) application built using Laravel for the API, the Filament admin panel for the web application, and Flutter for the mobile application. </p>

  <p>
    <a href="#">
      <img src="https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
    </a>
    <a href="#">
      <img src="https://img.shields.io/badge/Flutter-%2302569B.svg?style=for-the-badge&logo=Flutter&logoColor=white" alt="Flutter">
    </a>
  </p>
</div>

---

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Getting Started](#getting-started)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

## About

Kipit is a free, open-source POS application available for both web and Android. It helps you easily track daily profits and manage your store's inventory with a complete and user-friendly feature set.

## Features

- 🛒 Product Management
- 💰 Sales & Transaction Recording
- 📊 Daily Profit Reports
- 👥 Multi-tenant Support
- 📱 Mobile App (Flutter)
- 🖨️ Receipt Printing (USB ESC/POS)
- 📦 Product Import/Export
- 🔐 Role-based Access Control

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & NPM
- MySQL 8.0+

### Installation

1. Clone the repository: `git clone <your-repo-url>`
2. Navigate to the project directory: `cd kipit`
3. Install PHP dependencies: `composer install`
4. Install Node dependencies: `npm install`
5. Copy environment file: `cp .env.example .env`
6. Generate application key: `php artisan key:generate`
7. Run migrations: `php artisan migrate`
8. Build assets: `npm run build`
9. Start the development server: `php artisan serve`

## Contributing

We welcome contributions from the community! If you'd like to contribute to Kipit, please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the GPL-3.0 license - see the LICENSE file for details.

## Contact

For any inquiries or support, please open a discussion in the repository's discussion features.
