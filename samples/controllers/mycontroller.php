<?php

class MyController {
	
  public function getPage($req, $res) {
    pre($req->params);
    pre($req->data);
  }
	
  public function postPage($req, $res) {
    pre($req->params);
    pre($req->data);	
  }
	
}
