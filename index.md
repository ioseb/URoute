---
layout: default
title: URoute Library
---

# What is URoute?

URoute is a light-weight HTTP Routing library for PHP. It is optimized for quick development of robust, standards-compliant RESTful web-services.  

# Quick Introduction

To start serving RESTful HTTP requests, you need to go through two simple steps:
1. Setup URoute Library
1. Instantiate and configure a router object
1. Write callbacks/controllers.

## Setting Up URoute Library

You need to register a PHP script to handle all HTTP requests. For Apache it would look something like the following: 

```
RewriteEngine On
RewriteRule "(^|/)\." - [F]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !=/favicon.ico
RewriteRule ^ /your_www_root/api.php [NC,NS,L]
```

## Instantiating And Configuring A Router

For a very simple case of getting specific user object, the code of api.php would look something like:
```
require_once(dirname(__FILE__) . '/URoute/uroute.lib.php');

$router = new URoute_Router();

$router->addRoute(array(
      'path'     => '/users/{id}',
      'handlers' => array(
        'id'         => self::PATTERN_DIGIT, //regex
      ),
      'get'      => array('MyController', 'getPage'),
    )
);

$router->route();
```