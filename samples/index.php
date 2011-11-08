<?php

require_once(dirname(__FILE__) .'/../uroute.lib.php');

$router = new URoute_Router();

$router->addRoute(array(
      'path'     => '/pages/{id}/{categories}/{name}/{year}',
      'handlers' => array(
        'id'         => self::PATTERN_DIGIT, //regex
        'categories' => self::PATTERN_ARGS,  //regex
        'name'       => self::PATTERN_ANY,   //regex
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

function pre($o) {
  printf('<pre>%s</pre>', print_r($o, true));
}