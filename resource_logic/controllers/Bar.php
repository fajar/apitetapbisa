<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bar extends ST_Controller {
	
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
		$nearby = ($this->input->get('nearby') ? $this->input->get('nearby') : null);
		$latlng = ($this->input->get('latlng') ? $this->input->get('latlng') : null);
		$category = ($this->input->get('category') ? $this->input->get('category') : null);
		$suggest = ($this->input->get('suggest') ? $this->input->get('suggest') : null);
		$rate = ($this->input->get('rate') ? $this->input->get('rate') : null);
		$product = ($this->input->get('product') ? $this->input->get('product') : null);
		$location = ($this->input->get('location') ? $this->input->get('location') : null);
		$open = ($this->input->get('open') ? $this->input->get('open') : null);
		$promotion = ($this->input->get('promotion') ? $this->input->get('promotion') : null);
		
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = ($this->input->get('perpage') ? (int)$this->input->get('perpage') : 10);
		$offset = ($page-1) * $perpage;
		
		$filter = array(
			'keyword' => $keyword,
			'sort' => $sort,
			'category' => $category,
			'suggest' => $suggest,
			'rate' => $rate,
			'product' => $product,
			'location' => $location,
			'latlng' => $latlng,
			'nearby' => $nearby,
			'open' => $open,
			'promotion' => $promotion,
		);
		// Count
		$countFilter = (count(array_filter($filter, function($x) { return !empty($x); })) - 1);
		$filter['count'] = $countFilter;
		
		if($nearby && $latlng){
			$position = explode(',', $latlng);
			if(COUNT($position) >= 2){
				$filter['radius'] = array(
					'lat' => $position[0],
					'lng' => $position[1],
					'range' => 10,
				);
			}
		}
		
		//Total
		$total = $this->master_mod->result_bar(null, null, $filter);
		
		// Copy Result
		if($keyword){
			$filter['result_description'] = str_replace('@total', $total, translation('show_result_bar')).' '.translation('for').' "<b>'.$keyword.'</b>"';
		}else{
			$filter['result_description'] = str_replace('@total', $total, translation('show_result_bar'));
		}
		
		$data = array(
			'filter' => $filter,
			'page' => $page,
			'total_page' => ceil($total / $perpage),
			'total_data' => $total,
			'per_page' => $perpage,
			'result' => $this->master_mod->result_bar($perpage, $offset, $filter),
		);
		
		$this->response('success', $data);
	}
	
	public function qrcode()
	{
		//Login check
		$this->isloginaccount = $this->_loginCheck();
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('code', 'Code', 'trim|required|min_length[6]');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$code = $this->input->input_stream('code');
				
				//Extract
				$parse = parse_url($code);
				if(!isset($parse['path']) && !$parse['path']){
					$this->response('error', msg('ObjectNotFound', 'code'));
				}
				$split = explode('/', $parse['path']);
				if(!isset($split[2])){
					$this->response('error', msg('ObjectNotFound', 'code'));
				}
				$qrcode = $split[2];
				
				//Check Slug
				$detail = $this->db->limit(1)->get_where('bar', array('qrcode' => $qrcode, 'published' => 'publish'))->row();
				if(! $detail){
					$this->response('error', msg('ObjectNotFound', 'code'));
				}
				
				//Single per day 
				$today = $this->db
					->like('created', date('Y-m-d'), 'after')
					->limit(1)
					->get_where('contact_tracing', array('bar_id' => $detail->bar_id, 'member_id' => $this->isloginaccount['member_id']))
					->row();
				if($today){
					$this->detail($detail->bar_id);
				}
				
				$info = array(
					'bar_id' => $detail->bar_id,
					'fullname' => $this->isloginaccount['fullname'],
					'address' => $this->isloginaccount['address'],
					'phone' => $this->isloginaccount['phone'],
					'member_id' => $this->isloginaccount['member_id'],
					'safety_id' => 0, //implode(',', $safety_id)
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'agent' => $this->_selfAgent(),
				);
				
				if($this->db->insert('contact_tracing', $info)){
					$this->detail($detail->bar_id);
				}else{
					$this->response('error', msg('ObjectCannotBeSaved'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function detail($cid = null)
	{
		//Filter 
		if(! $cid){
			$cid = ($this->input->get('bar_id') ? $this->input->get('bar_id') : NULL);
		}
		$type = ($this->input->get('type') ? $this->input->get('type') : 'id');
		
		//Empty ID
		if(! $cid){ 
			$this->response('error', msg('NotNull', 'bar_id'));
		}
		
		//Check
		$detail = $this->master_mod->detail_bar($cid, $type);
		if(! $detail){
			$this->response('error', msg('ObjectNotFound', 'bar_id'));
		}else{
			//counter view
			$this->db->where(array('bar_id' => $detail->bar_id))->set('count_view','count_view+1', FALSE)->update('bar');
			
			//Filter related
			$filterRelated = array(
				'sort' => 'random',
				// 'radius' => array(
					// 'lat' => 0,
					// 'lng' => 0,
					// 'range' => 10,
				// ),
				'not_in_id' => array($detail->bar_id)
			);
			
			$data = array(
				'detail' => $detail,
				'related_bar' => $this->master_mod->result_bar(4, null, $filterRelated),
				'safety_related' => $this->master_mod->result_bar_safetyprotocol(array('bar_id' => $detail->bar_id)),
				'bar_photo' => $this->master_mod->result_bar_photo(array('bar_id' => $detail->bar_id)),
				'bar_opening' => $this->master_mod->result_bar_openinghour(array('bar_id' => $detail->bar_id)),
			);
			
			// operational_hour
			$data['detail']->operational_hour = '';
			$data['detail']->operational_status = translation('bar_close');
			$data['detail']->operational_status_int = 0;
			if($data['bar_opening']){
				$clock = now();
				if($this->isheaderallowed['language'] == 'en'){
					$list_days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
				}else{
					$list_days = array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
				}
				
				foreach($data['bar_opening'] AS $red){
					if(array_search($red->days, $list_days) == date('w')){
						if($clock >= strtotime($red->open) && $clock <= strtotime($red->close)){
							$data['detail']->operational_status = translation('bar_open');
							$data['detail']->operational_status_int = 1;
						}
						
						$data['detail']->operational_hour = date('H:i', strtotime($red->open)).' - '.date('H:i', strtotime($red->close)).' WIB';
						
						break;
					}
				}
			}
			
			$this->response('success', $data);
		}
	}
	
	public function review()
	{
		//Login check
		$this->isloginaccount = $this->_loginCheck();
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('bar_id', 'bar_id', 'trim|required');
			$this->form_validation->set_rules('rate', 'rate', 'trim|required|in_list[1,2,3,4,5]');
			$this->form_validation->set_rules('comment', 'comment', 'trim|required|min_length[10]|max_length[500]');
			
			if ($this->form_validation->run()==TRUE) {
				$bar_id = $this->input->input_stream('bar_id');
				$rate = $this->input->input_stream('rate');
				$comment = $this->input->input_stream('comment');
				
				//Emoticon encode
				$comment = $this->emoji->encode($comment);
				
				$info = array(
					'bar_id' => $bar_id,
					'member_id' => $this->isloginaccount['member_id'],
					'comment' => $comment,
					'rate' => $rate,
					'helpful' => 0,
					'unhelpful' => 0,
					'count_share' => 0,
					'published' => 'publish',
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'agent' => $this->_selfAgent(),
				);
				
				if($this->db->insert('bar_review', $info)){
					// Calc to bar review
					$detail = $this->master_mod->detail_bar($bar_id, 'id');
					if($detail){
						//Rated
						$rated = ($detail->rated > 0 ? (($detail->rated + $rate) /2) : $rate);
						$this->db->update('bar', array('rated' => $rated), array('bar_id' => $bar_id));
						
						//counter review
						$this->db->where(array('bar_id' => $bar_id))->set('count_review','count_review+1', FALSE)->update('bar');
					}
					
					$this->response('success', msg('system_success_review'));
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
