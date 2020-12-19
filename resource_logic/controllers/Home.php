<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends ST_Controller {
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
}
