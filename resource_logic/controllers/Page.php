<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Page extends ST_Controller {
	
	var $lang;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->lang = $this->isheaderallowed['language'];
		
		//Load models
		$this->load->model(array('master_mod'));
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function all()
	{
		//Check Slug
		$data = $this->master_mod->result_page();
		if(! $data){
			$this->response('error', msg('ObjectNotFound'));
		}
		
		$this->response('success', $data);
	}
	
	public function detail()
	{
		//Check Slug
		$id = $this->input->get('id');
		if(! $id){
			$this->response('error', msg('NotNull', 'id'));
		}
		
		$data = (array)$this->master_mod->detail_page($id, 'id');
		if(! $data){
			$this->response('error', msg('ObjectNotFound', 'id'));
		}
		
		$this->response('success', $data);
	}
}
