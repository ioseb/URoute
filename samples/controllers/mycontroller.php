<?php

class MyController {
	
  public function getPage($req, $res) {
    $res->add(pre($req->params));
    $res->add(pre($req->data));
    $res->send(301);    
  }
	
  public function postPage($req, $res) {
    $res->add(pre($req->params));
    $res->add(pre($req->data));
    $res->send(200);    
  }
	
}

function pre($o) {
  return strtr('<pre>%s</pre>', 
               array('%s' => print_r($o, true))
         );
}
