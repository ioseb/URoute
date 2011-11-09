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

	public function getPage($req, $res) {
		$res->add(pre($req->params));
	    $res->add(pre($req->data));
	    $res->send(301);    
	}

	public function postPage($req, $res) {
		$res->add(pre($req->params));
	    $res->add(pre($req->data));
	    $res->send(301);    
	}

}	

function pre($o) {
  return strtr('&lt;pre&gt;%s&lt;/pre&gt;', 
               array('%s' => print_r($o, true))
         );
}
</pre>

When invoked callbacks get two arguments:

    1. $req (request) object contains data parsed from the request, and can include properties like:
	  1. $params - which contains all the placeholders matched in the URL (e.g. the value of the "id" argument)
	  1. $data  - an array that contains HTTP data. In case of HTTP GET it is: parsed request parameters, for HTTP POST, PUT and DELETE requests: data variables contained in the HTTP Body of the request.
	  1. $version - version of the API if one is versioned (not yet implemented)
	  1. $format - data format that was requested (e.g. XML, JSON etc.)
	
	Following is an example request object:
	<pre>
URoute_Request Object
(
[params] => Array
(
	[id] => 234234
)

[data] => Array
	(
	  [api] => 46546456456
	)

[formats] => Array
	(
	  [0] => text/html
	  [1] => application/xhtml+xml
	  [2] => application/xml
	)

[encodings] => Array
	(
	  [0] => gzip
	  [1] => deflate
	  [2] => sdch
	)

[charsets] => Array
	(
	  [0] => ISO-8859-1
	  [1] => utf-8
	)

[languages] => Array
	(
	  [0] => en-US
	  [1] => en
	)

[version] => 
[method] => GET
[clientIP] => 172.30.25.142
[userAgent] => Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.106 Safari/535.2
[protocol] => HTTP/1.1
)		
	</pre>
2. $res (response) object is used to incrementally create content. You can add chunks of text to the output buffer by calling: $res->add (String) and once you are done you can send entire buffer to the HTTP client by issuing: $res->send(<HTTP_RESPONSE_CODE>). HTTP_RESPONSE_CODE is an optional parameter which defaults to (you guessed it:) 200.

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