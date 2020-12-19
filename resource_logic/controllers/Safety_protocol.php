<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Safety_protocol extends ST_Controller {
	
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
	
	public function search()
	{
		//Filter 
		$keyword = ($this->input->get('keyword') ? clean_html($this->input->get('keyword')) : null);
		$sort = ($this->input->get('sort') ? $this->input->get('sort') : 'desc');
		
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = ($this->input->get('perpage') ? (int)$this->input->get('perpage') : 10);
		$offset = ($page-1) * $perpage;
		
		$filter = array(
			'keyword' => $keyword,
			'sort' => $sort,
		);
		
		//Total
		$total = $this->master_mod->result_safety(null, null, $filter);
		
		$data = array(
			'filter' => $filter,
			'page' => $page,
			'total_page' => ceil($total / $perpage),
			'total_data' => $total,
			'per_page' => $perpage,
			'result' => $this->master_mod->result_safety($perpage, $offset, $filter),
		);
		
		$this->response('success', $data);
	}
	
	public function detail($cid = null)
	{
		//Filter 
		if(! $cid){
			$cid = ($this->input->get('safety_id') ? $this->input->get('safety_id') : NULL);
		}
		$type = ($this->input->get('type') ? $this->input->get('type') : 'id');
		
		//Empty ID
		if(! $cid){ 
			$this->response('error', msg('NotNull', 'safety_id'));
		}
		
		//Check
		$detail = $this->master_mod->detail_safety($cid, $type);
		if(! $detail){
			$this->response('error', msg('ObjectNotFound', 'safety_id'));
		}else{
			//counter view
			$this->db->where(array('safety_id' => $detail->safety_id))->set('count_view','count_view+1', FALSE)->update('safety_protocol');
			
			//Filter related
			$filterRelated = array(
				'sort' => 'random',
				'not_in_id' => array($detail->safety_id),
			);
			
			$data = array(
				'detail' => $detail,
				'related_safety' => $this->master_mod->result_safety(4, null, $filterRelated),
			);
			
			$this->response('success', $data);
		}
	}
	
	public function review()
	{
		//Login check
		$this->isloginaccount = $this->_loginCheck();
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('safety_id', 'safety_id', 'trim|required');
			$this->form_validation->set_rules('comment', 'comment', 'trim|required|min_length[10]|max_length[500]');
			
			if ($this->form_validation->run()==TRUE) {
				$safety_id = $this->input->input_stream('safety_id');
				$comment = $this->input->input_stream('comment');
				
				//Emoticon encode
				$comment = $this->emoji->encode($comment);
				
				$info = array(
					'safety_id' => $safety_id,
					'member_id' => $this->isloginaccount['member_id'],
					'comment' => $comment,
					'published' => 'publish',
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'agent' => $this->_selfAgent(),
				);
				
				if($this->db->insert('safety_review', $info)){
					$this->response('success', msg('system_success_comment'));
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
