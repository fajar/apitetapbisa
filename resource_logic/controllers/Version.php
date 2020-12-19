<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Version extends ST_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		//Login check
		$this->isloginaccount = $this->_loginCheck();
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function apps()
	{
		$data = array(
			'version' => $this->isloginaccount['build_version'],
			'download_url' => $this->isloginaccount['download_url'],
		);
		
		$this->response('success', $data);
	}
}
