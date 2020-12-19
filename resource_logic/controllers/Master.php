<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master extends ST_Controller {
	
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
	
	public function safety()
	{
		$data = $this->db
			->select('safety_id, '.$this->lang.'_title AS title')
			->order_by($this->lang.'_title', 'ASC')
			->get_where('safety_protocol', array('published' => 'publish'))
			->result();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'id' => $pro->safety_id,
				'value' => $pro->title
			);
		}
		
		$this->response('success', $json_data);
	}
	
	public function contact_topic()
	{
		$data = $this->db
			->select('topic_id, '.$this->lang.'_title AS title')
			->order_by($this->lang.'_title', 'ASC')
			->get_where('contact_topic', array('published' => 'publish'))
			->result();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'id' => $pro->topic_id,
				'value' => $pro->title
			);
		}

		$this->response('success', $json_data);
	}
	
	public function category()
	{
		$data = $this->db
			->select('category_id, '.$this->lang.'_title AS title')
			->order_by($this->lang.'_title', 'ASC')
			->get_where('category', array('published' => 'publish'))
			->result();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'id' => $pro->category_id,
				'value' => $pro->title
			);
		}

		$this->response('success', $json_data);
	}
	
	public function product()
	{
		$data = $this->db
			->select('product_id, title')
			->order_by('title', 'ASC')
			->get_where('product', array('published' => 'publish'))
			->result();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'id' => $pro->product_id,
				'value' => $pro->title
			);
		}

		$this->response('success', $json_data);
	}
	
	public function suggest()
	{
		$data = $this->db
			->select('suggest_id, '.$this->lang.'_title AS title')
			->order_by($this->lang.'_title', 'ASC')
			->get_where('suggest', array('published' => 'publish'))
			->result();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'id' => $pro->suggest_id,
				'value' => $pro->title
			);
		}

		$this->response('success', $json_data);
	}
	
	public function checkage()
	{
		if ($this->input->input_stream('date')) {
			list($yyyy, $mm, $dd) = explode('-', $this->input->input_stream('date'));
			
			//21+ Years
			if(checkdate($mm,$dd,$yyyy) == TRUE && $yyyy <= date('Y', strtotime('-21year'))){
				$this->response('success', msg('SuccessAction'));
			}else{
				$this->response('error', msg('age_description', 'date'));
			}
		}
		
		$this->response('error', msg('InsufficientPermissions'));
	}
	
	public function translation()
	{
		$data = $this->db
			->order_by('lang_key', 'ASC')
			->get('translation')
			->result();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'lang_key' => $pro->lang_key,
				'value' => ($this->lang == 'id' ? $pro->id : $pro->en),
			);
		}

		$this->response('success', $json_data);
	}
	
	public function location()
	{
		$data = $this->master_mod->result_location();

		$json_data = array();
		foreach($data as $pro){
			$json_data[] = array(
				'value' => $pro->location
			);
		}

		$this->response('success', $json_data);
	}
}
