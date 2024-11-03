# User module

Api base authentication and user management via laminas and pi engine

## 1. Important note

This is the first stable version of the user module, you can install it on production projects now, we will
work on improvement and testing this module in the next weeks

## 2. Installation

Follow the below steps to install the module via composer

### Step 1

Run the following to install this library:

```bash
$ composer require pi/user
```

### Step 2

Edit `config\modules.config.php` and add `'User'` end of array, for example like this

```
<?php
return [
    ...
    'Application',
    'User', // Add this line
];

```

### Step 3

Edit `composer.json` and add this line

```
    "autoload": {
        "psr-4": {
            ....
            "User\\": "vendor/pi/user/src/"
        }
    },
```

### Step 4

Run `composer dump-autoload`.

### Step 5

Open `data/schema.sql` and create tablas in your database

## 3. List of TODO tasks for this module

* [X] Rbac Authorization
* [X] Admin handler: profile section (add, edit, list, password, view)
* [X] Admin handler: Role section (add, edit, list)
* [X] Admin handler: Permission section (add, edit, list)
* [X] Handler (as web service) for check email, mobile, identity and password validation in registration
* [X] Handler (as web service) for check strong password
* [ ] DTO
* [ ] Write test cases and make test
* [ ] Write documents, (in-file, extra)
* [ ] Move Role and Permission sections from user module to core module