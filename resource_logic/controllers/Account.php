<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends ST_Controller {
	
	private $isloginaccount;
	
	public function __construct()
	{
		parent::__construct();
		
		//Login check
		$this->isloginaccount = $this->_loginCheck();
		
		//Status banned
		if($this->isloginaccount['status'] == 'banned'){
			$this->response('error', msg('InsufficientPermissions', '', 'logout'));
		}
		
		//Load models
		$this->load->model(array('member_mod', 'master_mod'));
		
		//hash password
		$this->load->library('bcrypt');
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function detail()
	{
		$data = (array)$this->member_mod->detail_member($this->isloginaccount['member_id'], 'member_id', null, 'public');
		if(! $data){
			$this->response('error', msg('ObjectNotFound'));
		}
		
		$this->response('success', $data);
	}
	
	public function update()
	{
		if($this->input->post()){
			$this->form_validation->set_rules('first_name', 'First name', 'trim|required|min_length[3]|max_length[100]');
			$this->form_validation->set_rules('last_name', 'Last name', 'trim|required|min_length[3]|max_length[100]');
			$this->form_validation->set_rules('address', 'address', 'trim|required|min_length[3]|max_length[255]');
			$this->form_validation->set_rules('phone', 'phone', 'trim|required|min_length[10]|max_length[15]');
			
			if($this->input->post('password')){
				$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
				$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|matches[password]');
			}
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$first_name = $this->input->post('first_name');
				$last_name = $this->input->post('last_name');
				$fullname = $first_name.' '.$last_name;
				$address = $this->input->post('address');
				$phone = $this->input->post('phone');
				$password = $this->input->post('password');
				
				//Data
				$info = array(
					'fullname' => $fullname,
					'first_name' => $first_name,
					'last_name' => $last_name,
					'address' => $address,
					'phone' => $phone,
					'updated' => date('Y-m-d H:i:s'),
					'updated_by' => $this->isloginaccount['member_id'],
					'agent' => $this->_selfAgent(),
				);
				
				//Status
				if(in_array($this->isloginaccount['status'], array('uncomplete'))){
					if($this->isloginaccount['isvalid_email'] == 'true'){
						$info['status'] = 'active';
					}
				}
				
				if($password){
					$info['password'] = $this->bcrypt->hash_password($password);
				}
				
				//upload file
				$config['upload_path'] = $this->storagepath.'/avatar';
				$config['allowed_types'] = 'jpg|png|jpeg';
				$config['overwrite'] = FALSE;
				$config['max_size']    = '2000';
				$config['max_width']  = '0';
				$config['max_height']  = '0';
				$config['encrypt_name'] = TRUE;
				$config['file_ext_tolower'] = TRUE;
				$this->load->library('upload', $config);
				$this->upload->initialize($config); 
				$uploads = $this->upload->do_upload('avatar');
				if($uploads === TRUE){
					$uploaded_image = $this->upload->data();
					$info['avatar'] = $uploaded_image['file_name'];
				}
				
				if($this->db->update('member', $info, array('member_id' => $this->isloginaccount['member_id']))){
					$inserted = (array)$this->member_mod->detail_member($this->isloginaccount['member_id'], 'member_id', null, 'public');
					
					$this->response('success', $inserted, msg('SuccessAction'));
				}else{
					$this->response('error', msg('SystemError'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function change_password()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('password_confirmation', 'Password', 'trim|required|min_length[6]|max_length[20]|matches[password]');
			$this->form_validation->set_rules('password', 'Password', 'trim|required');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$password = $this->input->input_stream('password_confirmation');
				
				//Check
				$check = $this->member_mod->detail_member($this->isloginaccount['member_id'], 'member_id');
				if(! $check){
					$this->response('error', msg('ObjectNotFound'));
				}
				
				$info = array(
					'password' => $this->bcrypt->hash_password($password),
					'updated' => date('Y-m-d H:i:s'),
					'updated_by' => $this->isloginaccount['member_id'],
				);
				
				if($this->db->update('member', $info, array('member_id' => $this->isloginaccount['member_id']))){
					$this->response('success', msg('SuccessAction'));
				}else{
					$this->response('error', msg('SystemError'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function bar()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id'], TRUE);
		if(! $detail){
			$this->response('error', msg('ObjectNotFound'));
		}
		
		$data = array(
			'detail' => $detail,
			'bar_photo' => $this->master_mod->result_bar_photo(array('bar_id' => $detail->bar_id)),
			'bar_opening' => $this->master_mod->result_bar_openinghour(array('bar_id' => $detail->bar_id)),
		);
			
		$this->response('success', $data);
	}
	
	public function bar_update()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		if($this->input->post()){
			$this->form_validation->set_rules('category_id', 'category_id', 'trim|required');
			$this->form_validation->set_rules('title', 'title', 'trim|required|min_length[3]|max_length[100]');
			$this->form_validation->set_rules('id_description', 'id_description', 'trim|required');
			$this->form_validation->set_rules('en_description', 'en_description', 'trim|required');
			$this->form_validation->set_rules('published', 'published', 'trim|required');
			
			if($this->form_validation->run()) {
				$info = array(
					'category_id' => $this->input->post('category_id'),
					'title' => $this->input->post('title'),
					'id_description' => $this->input->post('id_description', FALSE),
					'en_description' => $this->input->post('en_description', FALSE),
					'lat' => $this->input->post('lat'),
					'lng' => $this->input->post('lng'),
					'address' => $this->input->post('address'),
					'published' => (in_array($this->input->post('published'), array('publish','unpublish')) ? $this->input->post('published') : 'publish'),
					'updated' => date('Y-m-d H:i:s'),
					'updated_by' => $this->isloginaccount['member_id'],
					'meta_title' => $this->input->post('meta_title'),
					'meta_description' => $this->input->post('meta_description'),
					'meta_keyword' => $this->input->post('meta_keyword'),
					'location' => $this->input->post('location'),
					'facebook' => $this->input->post('facebook'),
					'instagram' => $this->input->post('instagram'),
					'website' => $this->input->post('website'),
					'id_prediction_open' => $this->input->post('id_prediction_open'),
					'en_prediction_open' => $this->input->post('en_prediction_open'),
					'capacity' => $this->input->post('capacity'),
				);
				
				//Upload file
				$config['upload_path'] = $this->storagepath.'/bar';
				$config['allowed_types'] = 'jpg|png|jpeg';
				$config['overwrite'] = FALSE;
				$config['max_size']    = '2000';
				$config['max_width']  = '0';
				$config['max_height']  = '0';
				$config['encrypt_name'] = TRUE;
				$config['file_ext_tolower'] = TRUE;
				$this->load->library('upload', $config);
				$this->upload->initialize($config); 
				$uploads = $this->upload->do_upload('cover');
				if($uploads === TRUE){
					$uploaded_image = $this->upload->data();
					$info['cover'] = $uploaded_image['file_name'];
				}
				
				if($this->db->update('bar', $info, array('bar_id' => $detail->bar_id))){
					$bar_id = $detail->bar_id;
					
					//Product 
					if($this->input->post('product')){
						$productArr = json_decode($this->input->post('product'));
						if(is_array($productArr)){
							//Reset
							$this->db->delete('bar_product', array('bar_id' => $bar_id));
							
							$product = array();
							foreach($productArr AS $red){
								if(isset($red) && $red != ''){
									$product[] = array(
										'bar_id' => $bar_id,
										'product_id' => $red,
										'created' => date('Y-m-d H:i:s'),
										'created_by' => $this->isloginaccount['member_id'],
									);
								}
							}
							
							if($product){
								$this->db->insert_batch('bar_product', $product);
							}
						}
					}
					
					//Suggest 
					if($this->input->post('suggest')){
						$suggestArr = json_decode($this->input->post('suggest'));
						if(is_array($suggestArr)){
							//Reset
							$this->db->delete('bar_suggest', array('bar_id' => $bar_id));
							
							$suggest = array();
							foreach($suggestArr AS $red){
								if(isset($red) && $red != ''){
									$suggest[] = array(
										'bar_id' => $bar_id,
										'suggest_id' => $red,
										'created' => date('Y-m-d H:i:s'),
										'created_by' => $this->isloginaccount['member_id'],
									);
								}
							}
							
							if($suggest){
								$this->db->insert_batch('bar_suggest', $suggest);
							}
						}
					}
					
					//Safety 
					if($this->input->post('safety')){
						$safetyArr = json_decode($this->input->post('safety'));
						if(is_array($safetyArr)){
							//Reset
							$this->db->delete('bar_safetyprotocol', array('bar_id' => $bar_id));
							
							$safety = array();
							foreach($safetyArr AS $red){
								if(isset($red) && $red != ''){
									$safety[] = array(
										'bar_id' => $bar_id,
										'safety_id' => $red,
										'created' => date('Y-m-d H:i:s'),
										'created_by' => $this->isloginaccount['member_id'],
									);
								}
							}
							
							if($safety){
								$this->db->insert_batch('bar_safetyprotocol', $safety);
							}
						}
					}
					
					$this->bar();
				}else{
					$this->response('error', msg('SystemError'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}else{
			$this->response('success', $detail);
		}
	}
	
	public function bar_opening()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('days', 'days', 'trim|required');
			$this->form_validation->set_rules('open', 'open', 'trim|required');
			$this->form_validation->set_rules('close', 'close', 'trim|required');
			
			if($this->form_validation->run()) {
				$info = array(
					'bar_id' => $detail->bar_id,
					'days' => $this->input->input_stream('days'),
					'open' => $this->input->input_stream('open'),
					'close' => $this->input->input_stream('close'),
					'created' => date('Y-m-d H:i:s'),
					'created_by' => $this->isloginaccount['member_id'],
				);
				
				if($this->db->insert('bar_openinghour', $info)){
					$id = $this->db->insert_id();
					
					$data = array(
						'node_id' => (string)$id, 
						'days' => translation_day($info['days']), 
						'open' => date('H:i', strtotime($info['open'])), 
						'close' => date('H:i', strtotime($info['close'])),
					);
					
					$this->response('success', $data);
				}else{
					$this->response('error', msg('SystemError'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}else{
			$data = $this->master_mod->result_bar_openinghour(array('bar_id' => $detail->bar_id));
			$this->response('success', $data);
		}
	}
	
	public function bar_opening_delete()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		$cid = $this->input->get('node_id');
		if(! $cid){
			$this->response('error', msg('NotNull', 'node_id'));
		}
		
		$detail = $this->db->limit(1)->get_where('bar_openinghour', array('node_id' => $cid, 'bar_id' => $detail->bar_id))->row();
		if(! $detail){
			$this->response('error', msg('ObjectNotFound'));
		}else{
			if($this->db->delete('bar_openinghour', array('node_id' => $cid))){
				$this->response('success', msg('SuccessAction'));
			}else{
				$this->response('error', msg('SystemError'));
			}
		}
	}
	
	public function bar_photo()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		//Upload file
		$config['upload_path'] = $this->storagepath.'/bar';
		$config['allowed_types'] = 'jpg|png|jpeg';
		$config['overwrite'] = FALSE;
		$config['max_size']    = '2000';
		$config['max_width']  = '0';
		$config['max_height']  = '0';
		$config['encrypt_name'] = TRUE;
		$config['file_ext_tolower'] = TRUE;
		$this->load->library('upload', $config);
		$this->upload->initialize($config); 
		$uploads = $this->upload->do_upload('cover');
		if($uploads === TRUE){
			$uploaded_image = $this->upload->data();
			
			$info = array(
				'bar_id' => $detail->bar_id,
				'cover' => $uploaded_image['file_name'],
				'published' => 'publish',
				'created' => date('Y-m-d H:i:s'),
				'updated' => date('Y-m-d H:i:s'),
				'created_by' => $this->isloginaccount['member_id'],
			);
			
			if($this->db->insert('bar_photo', $info)){
				$id = $this->db->insert_id();
				
				$data = array(
					'node_id' => (string)$id, 
					'cover' => s3content('bar/'.$info['cover']), 
					'published' => $info['published'], 
					'created' => $info['created'], 
					'updated' => $info['updated']
				);
				
				$this->response('success', $data);
			}else{
				$this->response('error', msg('SystemError'));
			}
		}else{
			$data = $this->master_mod->result_bar_photo(array('bar_id' => $detail->bar_id));
			$this->response('success', $data);
		}
	}
	
	public function bar_photo_publish()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		$cid = $this->input->get('node_id');
		if(! $cid){
			$this->response('error', msg('NotNull', 'node_id'));
		}
		
		$detail = $this->db->limit(1)->get_where('bar_photo', array('node_id' => $cid, 'bar_id' => $detail->bar_id))->row();
		if(! $detail){
			$this->response('error', msg('ObjectNotFound'));
		}else{
			$info = array(
				'published' => ($detail->published == 'publish' ? 'unpublish' : 'publish'),
				'updated' => date('Y-m-d H:i:s'),
				'updated_by' => $this->isloginaccount['member_id'],
			);
			
			if($this->db->update('bar_photo', $info, array('node_id' => $cid))){
				$data = array(
					'node_id' => $cid, 
					'cover' => s3content('bar/'.$detail->cover), 
					'published' => $detail->published, 
					'created' => $detail->created, 
					'updated' => $detail->updated
				);
				
				$this->response('success', $data);
			}else{
				$this->response('error', msg('SystemError'));
			}
		}
	}
	
	public function promotion()
	{
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = 10;
		$offset = ($page-1) * $perpage;
		
		$filter = array();
		
		//Total
		$total = $this->master_mod->result_promotion(null, null, $filter);
		
		$data = array(
			'filter' => $filter,
			'page' => $page,
			'total_page' => ceil($total / $perpage),
			'total_data' => $total,
			'per_page' => $perpage,
			'result' => $this->master_mod->result_promotion($perpage, $offset, $filter),
		);
		
		$this->response('success', $data);
	}
	
	public function review()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		//Filter 
		$sort = ($this->input->get('sort') ? $this->input->get('sort') : 'desc');
		
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = 12;
		$offset = ($page-1) * $perpage;
		
		$filter = array(
			'sort' => $sort,
			'owner_id' => $this->isloginaccount['member_id'],
		);
		
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
	
	public function contact_us()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		//Filter 
		$sort = ($this->input->get('sort') ? $this->input->get('sort') : 'desc');
		
		//Pagination
		$page = ($this->input->get('page') ? (int)$this->input->get('page') : 1);
		$perpage = 12;
		$offset = ($page-1) * $perpage;
		
		$filter = array(
			'sort' => $sort,
			'bar_id' => $detail->bar_id,
		);
		
		//Total
		$total = $this->master_mod->result_contact_us(null, null, $filter);
		
		$data = array(
			'filter' => $filter,
			'page' => $page,
			'total_page' => ceil($total / $perpage),
			'total_data' => $total,
			'per_page' => $perpage,
			'result' => $this->master_mod->result_contact_us($perpage, $offset, $filter),
		);
		
		$this->response('success', $data);
	}
	
	public function contact_flag()
	{
		$detail = $this->master_mod->detail_ownerbar($this->isloginaccount['member_id']);
		if(! $detail){
			$this->response('error', msg('InsufficientPermissions'));
		}
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('id', 'ID', 'trim|required');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$id = $this->input->input_stream('id');
		
				$detail = $this->db->limit(1)->get_where('contact_us', array('bar_id' => $detail->bar_id, 'node_id' => $id))->row();
				if(! $detail){
					$this->response('error', msg('ObjectNotFound'));
				}else{
					$info = array(
						'status' => ($detail->status == 'waiting' ? 'answered' : 'waiting'),
						'updated' => date('Y-m-d H:i:s'),
						'updated_by' => $this->isloginaccount['member_id'],
					);
					
					if($this->db->update('contact_us', $info, array('node_id' => $id))){
						$this->response('success', msg('SuccessAction'));
					}else{
						$this->response('error', msg('SystemError'));
					}
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
}