<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Review extends ST_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		//Load models
		$this->load->model(array('member_mod', 'master_mod'));
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function bar()
	{
		//Filter 
		$bar_id = ($this->input->get('bar_id') ? $this->input->get('bar_id') : NULL);
		$sort = ($this->input->get('sort') ? $this->input->get('sort') : 'desc');
		
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = 12;
		$offset = ($page-1) * $perpage;
		
		$filter = array(
			'sort' => $sort,
		);
		
		//Check
		if($bar_id){
			$detail = $this->master_mod->detail_bar($bar_id, 'id');
			if(! $detail){
				$this->response('error', msg('ObjectNotFound', 'bar_id'));
			}
			
			$filter['bar_id'] = $detail->bar_id;
		}
		
		//Total
		$total = $this->master_mod->result_bar_review(null, null, $filter);
		
		$data = array(
			'filter' => $filter,
			'page' => $page,
			'total_page' => ceil($total / $perpage),
			'total_data' => $total,
			'per_page' => $perpage,
			'result' => $this->master_mod->result_bar_review($perpage, $offset, $filter),
		);
		
		$this->response('success', $data);
	}
	
	public function safety_protocol()
	{
		//Filter 
		$safety_id = ($this->input->get('safety_id') ? $this->input->get('safety_id') : NULL);
		$sort = ($this->input->get('sort') ? $this->input->get('sort') : 'desc');
		
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = 12;
		$offset = ($page-1) * $perpage;
		
		$filter = array(
			'sort' => $sort,
		);
		
		//Check
		if($safety_id){
			$detail = $this->master_mod->detail_safety($safety_id, 'id');
			if(! $detail){
				$this->response('error', msg('ObjectNotFound', 'safety_id'));
			}
			
			$filter['safety_id'] = $detail->safety_id;
		}
		
		//Total
		$total = $this->master_mod->result_safety_review(null, null, $filter);
		
		$data = array(
			'filter' => $filter,
			'page' => $page,
			'total_page' => ceil($total / $perpage),
			'total_data' => $total,
			'per_page' => $perpage,
			'result' => $this->master_mod->result_safety_review($perpage, $offset, $filter),
		);
		
		$this->response('success', $data);
	}
	
	public function helpful()
	{
		//Login check
		$this->isloginaccount = $this->_loginCheck();
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('id', 'id', 'trim|required');
			$this->form_validation->set_rules('type', 'type', 'trim|required|in_list[helpful,unhelpful]');
			
			if ($this->form_validation->run()==TRUE) {
				$review_id = $this->input->input_stream('id');
				$type = $this->input->input_stream('type');
				
				// Check unique
				$check = $this->db
					->limit(1)
					->get_where('bar_review_helpfull', array('review_id' => $review_id, 'member_id' => $this->isloginaccount['member_id']))
					->row();
					
				if($check){
					$this->response('error', msg('system_request_not_allowed'));
				}
				
				//Log
				$info = array(
					'review_id' => $review_id,
					'member_id' => $this->isloginaccount['member_id'],
					'action' => $type,
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'agent' => $this->_selfAgent(),
				);
				
				if($this->db->insert('bar_review_helpfull', $info)){
					//counter
					if($type == 'helpful'){
						$this->db->where(array('review_id' => $review_id))->set('helpful','helpful+1', FALSE)->update('bar_review');
					}else{
						$this->db->where(array('review_id' => $review_id))->set('unhelpful','unhelpful+1', FALSE)->update('bar_review');
					}
					
					//Count history
					$detail = $this->db
						->limit(1)
						->get_where('bar_review', array('review_id' => $review_id))
						->row();
					
					if($type == 'helpful'){
						$this->response('success', array('count' => $detail->helpful), msg('system_success_review'));
					}else{
						$this->response('success', array('count' => $detail->unhelpful), msg('system_success_review'));
					}
				}else{
					$this->response('error', msg('ObjectCannotBeSaved'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
}
