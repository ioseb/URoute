---
layout: default
title: URoute Library
---

# What is URoute?

URoute is a light-weight HTTP Routing library for PHP. It is optimized for quick development of robust, standards-compliant RESTful web-services.  

# Quick Introduction

To start serving RESTful HTTP requests, you need to go through three simple steps:

1. Setup URoute Library
1. Instantiate and configure a router object
1. Write callbacks/controllers.

## Setting Up URoute Library

You need to register a PHP script to handle all HTTP requests. For Apache it would look something like the following: 

<pre>
RewriteEngine On
RewriteRule "(^|/)\." - [F]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !=/favicon.ico
RewriteRule ^ /your_www_root/api.php [NC,NS,L]
</pre>

## Instantiating And Configuring A Router

For a very simple case of getting specific user object, the code of api.php would look something like:

<pre>
require_once(dirname(__FILE__) . '/URoute/uroute.lib.php');

$router = new URoute_Router();

$router->addRoute(array(
      'path'     => '/users/{id}',
      'handlers' => array(
        'id'         => URoute_Constants::PATTERN_DIGIT, //regex
      ),
      'get'      => array('MyController', 'getPage'),
    )
);

$router->route();
</pre>

In this example, {id} is a URI parameter, so `MyController->getPage()` function will get control to serve URLs like:

* http://example.com/users/32424
* http://example.com/users/23

However, we asked the library to ascertain that the {id} parameter is a number by attaching a validating handler: "URoute_Constants::PATTERN_DIGIT" to it. As such, following URL will not be handed over to the `MyController->getPage()` callback:

* http://example.com/users/ertla
* http://example.com/users/asda32424
* http://example.com/users/32424sfsd
* http://example.com/users/324sdf24

# Example Controllers/Callbacks

<pre>
class MyController {

	public function getPage($params, $data) {
	    pre($params);
	    pre($data);
	}

	public function postPage($params, $data) {
		pre($params);
		pre($data);
	}

}	

function pre($o) {
  printf('&lt;pre&gt;%s&lt;/pre&gt;', print_r($o, true));
}
</pre>

When invoked callbacks get two arguments:

1. $params array contains all the placeholders matched in the URL (e.g. the value of the "id" argument)
2. $data array contains HTTP data, which in the case of HTTP GET is: parsed request parameters, for HTTP POST, PUT and DELETE: data variables contained in the HTTP Body of the request.

# A More Advanced Router Example

<pre>
require_once(dirname(__FILE__) .'/../uroute.lib.php');

$router = new URoute_Router();

$router->addRoute(array(
      'path'     => '/pages/{id}/{categories}/{name}/{year}',
      'handlers' => array(
        'id'         => URoute_Constants::PATTERN_DIGIT, //regex
        'categories' => URoute_Constants::PATTERN_ARGS,  //regex
        'name'       => URoute_Constants::PATTERN_ANY,   //regex
        'year'       => 'handle_year',       //callback function
      ),
      'get'      => array('MyController', 'getPage'),
      'post'     => array('MyController', 'postPage'),
      'file'     => 'controllers/mycontroller.php'
    )
);

$router->route();

function handle_year($param) {
  return preg_match('~^\d{4}$~', $param) ? array(
    'ohyesdd' => $param,
    'ba' => 'booooo',
  ) : null;
}
</pre>