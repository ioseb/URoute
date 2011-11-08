<?php

/** Invalid path exception **/
class URoute_InvalidPathException extends Exception {}
/** File not found exception **/
class URoute_CallbackFileNotFoundException extends Exception {}
/** Invalid callback exception **/
class URoute_InvalidCallbackException extends Exception {}
/** Invalid URI Parameter exception **/
class URoute_InvalidURIParameterException extends Exception {}

/**
* Handy regexp patterns for common types of URI parameters.
*/
final class URoute_Constants {
  const PATTERN_ARGS       = '?(?P<%s>(?:/.+)+)';
  const PATTERN_ARGS_ALPHA = '?(?P<%s>(?:/[-\w]+)+)';
  const PATTERN_WILD_CARD  = '(?P<%s>.*)';
  const PATTERN_ANY        = '(?P<%s>(?:/?[^/]*))';
  const PATTERN_ALPHA      = '(?P<%s>(?:/?[-\w]+))';
  const PATTERN_NUM        = '(?P<%s>\d+)';
  const PATTERN_DIGIT      = '(?P<%s>\d+)';
  const PATTERN_YEAR       = '(?P<%s>\d{4})';
  const PATTERN_MONTH      = '(?P<%s>\d{1,2})';
  const PATTERN_DAY        = '(?P<%s>\d{1,2})';
  const PATTERN_MD5        = '(?P<%s>[a-z0-9]{32})';  
}

/**
* Callback class for route-processing.
*/
class URoute_Callback_Util {
  
  private static function loadFile($file) {
    if (file_exists($file)) {
      if (!in_array($file, get_included_files())) {
        include($file);
      }
    } else {
      throw new URoute_CallbackFileNotFoundException('Controller file not found');
    }
  }
  
  public static function getCallback($callback, $file = null) {
  
    try {
    
      if ($file) {
        self::loadFile($file);
      }
      
      if (is_array($callback)) {
          
        $method = new ReflectionMethod(array_shift($callback), array_shift($callback));
        
        if ($method->isPublic()) {
          if ($method->isStatic()) {
            $callback = array($method->class, $method->name);
          } else {
            $callback = array(new $method->class, $method->name);
          }
        }
         
      } else if (is_string($callback)) {
        $callback = $callback;
      }
      
      if (is_callable($callback)) {
        return $callback;
      }

      throw new URoute_InvalidCallbackException("Invalid callback");
      
    } catch (Exception $ex) {
      throw $ex;
    }
    
  }
  
}

class URoute_Template {
  
  private static $globalQueryParams = array();
  
  private $template  = null;
  private $params    = array();
  private $callbacks = array();
  
  public function __construct($path) {
    if ($path{0} != '/') {
      $path = '/'. $path;
    }
    $this->template = rtrim($path, '\/');
  }
  
  public function getTemplate() {
    return $this->template;
  }
  
  public function getExpression() {
    $expression = $this->template;
    
    if (preg_match_all('~(?P<match>\{(?P<name>.+?)\})~', $expression, $matches)) {
      $expressions = array_map(array($this, 'pattern'), $matches['name']);
      $expression  = str_replace($matches['match'], $expressions, $expression);
    }
    
    return sprintf('~^%s$~', $expression);
  }
  
  public function pattern($token, $pattern = null) {
    static $patterns = array();
    
    if ($pattern) {
      if (!isset($patterns[$token])) {
        $patterns[$token] = $pattern;
      } 
    } else {
      
      if (isset($patterns[$token])) {
        $pattern = $patterns[$token];
      } else {
        $pattern = self::PATTERN_ANY;
      }
      
      if ((is_string($pattern) && is_callable($pattern)) || is_array($pattern)) {
        $this->callbacks[$token] = $pattern;
        $patterns[$token] = $pattern = self::PATTERN_ANY;
      }
      
      return sprintf($pattern, $token);
       
    }    
  }
  
  public function addQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = self::PATTERN_ANY;
    }
    $this->params[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public static function addGlobalQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = self::PATTERN_ANY;
    }
    self::$globalQueryParams[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public function match($uri) {
    
    try {
    
      $uri = rtrim($uri, '\/');
      
      if (preg_match($this->getExpression(), $uri, $matches)) {
        
        foreach($matches as $k=>$v) {
          if (is_numeric($k)) {
            unset($matches[$k]);
          } else {
            
            if (isset($this->callbacks[$k])) {              
              $callback = URoute_Callback::getCallback($this->callbacks[$k]);
              $value    = call_user_func($callback, $v);
              if ($value) {
                $matches[$k] = $value;
              } else {
                throw new URoute_InvalidURIParameterException('Ivalid parameters detected');
              }
            }
            
            if (strpos($v, '/') !== false) {
              $matches[$k] = explode('/', trim($v, '\/'));
            }
          }
        }
  
        $params = array_merge(self::$globalQueryParams, $this->params);
  
        if (!empty($params)) {
          
          $matched = false;
          
          foreach($params as $name=>$param) {
            
            if (!isset($_GET[$name]) && $param->value) {
              $_GET[$name] = $param->value;
              $matched = true;
            } else if ($param->pattern && isset($_GET[$name])) {
              $result = preg_match(sprintf('~^%s$~', $param->pattern), $_GET[$name]);
              if (!$result && $param->value) {
                $_GET[$name] = $param->value;
                $result = true;
              }
              $matched = $result;
            } else {
              $matched = false;
            }          
            
            if ($matched == false) {
              throw new Exception('Request do not match');
            }
            
          }
          
        }
        
        return $matches;
        
      }
      
    } catch(Exception $ex) {
      throw $ex;
    }
    
  }
  
  public static function regex($pattern) {
    return '(?P<%s>' . $pattern . ')';
  }
  
}

class URoute_Router {
  
  protected $routes  = array();
  protected static $methods = array('get', 'post', 'put', 'delete', 'head', 'options');
  
  /**
  * Add a new route to the configured list of routes
  */
  public function addRoute($params) {
    
    if (!empty($params['path'])) {
      
      $template = new URoute_Template($params['path']);
      
      if (!empty($params['handlers'])) {
        foreach ($params['handlers'] as $key => $pattern) {
           $template->pattern($key, $pattern);
        }
      }
            
      $methods = array_intersect(self::$methods, array_keys($params));

      foreach ($methods as $method) {
        $this->routes[$method][$params['path']] = array(
          'template' => $template,
          'callback' => $params[$method],
          'file'     => $params['file'],
        );
      }
      
    }
    
  }
  
  private static function getRequestMethod() {
    return strtolower($_SERVER['REQUEST_METHOD']);
  }
  
  private function getRoutes() {
    $method = self::getRequestMethod();
    $routes = empty($this->routes[$method]) ? array() : $this->routes[$method];
    return $routes;
  }
  
  public function route($uri=null) {
  
    if (empty($uri)) {
      $tokens = parse_url($_SERVER['REQUEST_URI']);
      $uri = $tokens['path'];
    }
  
    $routes = $this->getRoutes();
        
    try {
    
      foreach ($routes as $route) {
        
        $params = $route['template']->match($uri);
  
        if (!is_null($params)) {
          $callback = URoute_Callback_Util::getCallback($route['callback'], $route['file']);
          $data = $this->getEnv();
          return call_user_func($callback, $params, $data);
        }        
      }
      
      throw new URoute_InvalidPathException('Invalid path');
      
    } catch (Exception $ex) {
      throw $ex;
    }
    
  }
  
  private function getEnv() {
    $env = new stdClass;
    
    $env->method = $_SERVER['REQUEST_METHOD'];
    
    $env->clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
    $env->clientIP = (empty($env->clientIP) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "";  
    
    $env->userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? "" : $_SERVER['HTTP_USER_AGENT'];    
    
    switch ($env->method) {
        case "GET":
            $env->requestData = $_GET;
            break;                
        case "POST":
            $env->requestData = $_POST;
            break;                
        case "PUT":
        case "DELETE":
            parse_str(file_get_contents("php://input"), $env->requestData);
            break;                
    }
    
    return (array)$env;
    
  }
  
} // end URoute_Router

