<p align="center"><a href="https://github.com/omaicode/pagify" target="_blank"><img src="https://raw.githubusercontent.com/omaicode/pagify/master/logo/pagify_logo.png" width="400" alt="Pagify Logo"></a></p>

<p align="center">
<a href="https://packagist.org/packages/omaicode/pagify"><img src="https://img.shields.io/packagist/dt/omaicode/pagify" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/omaicode/pagify"><img src="https://img.shields.io/packagist/v/omaicode/pagify" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/omaicode/pagify"><img src="https://img.shields.io/packagist/l/omaicode/pagify" alt="License"></a>
</p>

## About Pagify

Pagify is a open-source content management system (CMS) built with Laravel. It provides a simple and flexible platform for creating and managing websites, blogs, and other types of content-driven applications.

## Prequisites

Before installing Pagify, make sure you have the following prerequisites:
- PHP 8.2 or higher
- Composer
- Node v22 or higher
- A web server (e.g., Apache, Nginx)
- A database server (e.g., MySQL, PostgreSQL)

## Features

- User-friendly interface for content creation and management
- Support for multiple content types (pages, posts, etc.)
- Customizable themes and templates
- Built-in SEO tools
- Role-based access control for users
- Extensible architecture for adding custom functionality
- Responsive design for mobile and desktop devices

## Installation

To install Pagify, follow these steps:

```bash
composer create pagify/pagify your-project-name
```

## Development workflow

Project commands are standardized in the runbook:

- [docs/runbook.md](docs/runbook.md)
- [docs/theme-development.md](docs/theme-development.md)

Quick commands:

```bash
composer setup
composer dev
php artisan test
```

Admin UI theme can be switched by setting `ADMIN_THEME` (default: `default`):

```bash
export ADMIN_THEME=v2
composer dev
```

Frontend public theme is resolved from `themes/main/{THEME_NAME}` and supports site-level activation via Theme Manager.

Environment variables:

- `FRONTEND_THEMES_BASE_PATH` (default: `themes/main`)
- `FRONTEND_THEME` (default: `default`)
- `FRONTEND_THEME_FALLBACK` (default: `default`)

## Contributing
We welcome contributions to Pagify! If you would like to contribute, please fork the repository and submit a pull request with your changes. Make sure to follow our coding standards and include tests for any new features or bug fixes.
