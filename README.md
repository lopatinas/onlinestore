# Onlinestore

## Installation

* Create database
* Copy `config/parameters.yaml.example` to `config/parameters.yaml` and change database credentials in `database_url`
* Run `composer install` to install dependencies
* Run `vendor/bin/doctrine orm:schema-tool:create` to create tables
* Run `bin/console product:create` to fill database with products
* Go to `public` directory and run `php -S localhost:8081`
* Open http://localhost:8081
* Profit

## Requirements

* PHP >=7.2
* MySQL >=5.7.8