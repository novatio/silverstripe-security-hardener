# Security Hardener for SilverStripe
Module that makes it possible to enable login hardening from the admin.

Makes use of [camfindlay/silverstripe-twofactorauth](https://github.com/camfindlay/silverstripe-twofactorauth).

## Installation
```bash
composer require novatio/silverstripe-security-hardener
```

## Configuration
Configuration is done in the settings section of the admin, tab "Security", where you can:
- enable and manage the settings for "Login Lockout";
- enable "Two Factor Auth".