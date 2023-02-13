# Tnapf/MySQLSessions

A sessioninterfaces implementation for using a MySQL database as session storage

# Installation

`composer require tnapf/mysqlsessions`

# Usage

## Setting up table

First, use this SQL code to create your sessions table

```sql
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE `sessions` (
  `id` varchar(16) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `expires` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);
COMMIT;
```

## Creating Session Controller

Next create a PDO Connection (will be using `tnapf/pdo` for building the driver)

```php
use Tnapf\Pdo\Driver;
use Tnapf\MysqlSessions\Controller;

$driver = Driver::createMySqlDriver("root", "password", "database")->connect();

/** @var PDO $driver */
$driver = $driver->driver;

$sessions = new Controller($driver);
```

After creating the driver construct `Tnapf\MysqlSessions\Controller` using the PDO object as the first argument.

## Creating a session

```php
$session = $session->create(); // you can supply a timestamp in seconds for when the cookie should expire; default is 7 days

header($session->setCookieHeader()); // sends a set-cookie header with the session id
```

## Setting session variables

```php
$session->var = "foo";

// or

$session->set("var", "foo");
```

## Unsetting session variables

```php
unset($session->var);

// or

$session->unset("var");
```

## Deleting sessions

```php
$sessions->delete($session);

// or

$sessions->delete($session->id);
```