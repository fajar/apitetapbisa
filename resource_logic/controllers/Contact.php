<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contact extends ST_Controller {
	
	var $isloginaccount;
	
	public function __construct()
	{
		parent::__construct();
		
		//Login check callback
		$this->isloginaccount = [];
		if($this->isheaderallowed['mobile_session']){
			$this->isloginaccount = $this->_loginCheck();
		}
		
		//hash password
		$this->load->library('bcrypt');
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function tracing()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('bar_id', 'bar_id', 'trim|required');
			
			if(!$this->isloginaccount){
				$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
				$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
				$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|matches[password]');
				$this->form_validation->set_rules('fullname', 'fullname', 'trim|required|min_length[3]|max_length[100]');
				$this->form_validation->set_rules('address', 'address', 'trim');
				$this->form_validation->set_rules('phone', 'phone', 'trim|required|min_length[10]|max_length[15]');
			}
			
			if ($this->form_validation->run()==TRUE) {
				$bar_id = $this->input->input_stream('bar_id');
				$email = ($this->isloginaccount ? $this->isloginaccount['email'] : $this->input->input_stream('email'));
				$password = $this->input->input_stream('password');
				$fullname = ($this->isloginaccount ? $this->isloginaccount['fullname'] : $this->input->input_stream('fullname'));
				$address = ($this->isloginaccount ? $this->isloginaccount['address'] : $this->input->input_stream('address'));
				$phone = ($this->isloginaccount ? $this->isloginaccount['phone'] : $this->input->input_stream('phone'));
				
				// Member register 
				if(! $this->isloginaccount){
					$check = $this->db->limit(1)->get_where('member', array('email' => $email))->row();
					if(! $check){
						//Non Symbolic email
						if(strpos($email, '+') == TRUE){
							$this->response('error', msg('system_email_invalid', 'email'));
						}
						
						//Blacklist email
						$blacklist = $this->db->limit(1)->get_where('blacklist', array('domain' => substr(strrchr($email, "@"), 1)))->row();
						if($blacklist){
							$this->response('error', msg('system_email_blacklist', 'email'));
						}
						
						//Check domain
						list($user_email, $domain_email) = explode('@', $email);
						if(checkdnsrr($domain_email) === FALSE){
							$this->response('error', msg('system_email_inactive', 'email'));
						}
						
						// Name
						$name = explode(' ', $fullname);
						$first_name = (COUNT($name) >= 2 ? $name[0] : $fullname);
						$last_name = (COUNT($name) >= 2 ? $name[1] : $fullname);
						
						//Slug Title for URL
						$slug_candidate = slug(clean_html($fullname));
						$slug_candidate = rtrim($slug_candidate, '-0123456789');
						$possible_conflicts = array_map(
						create_function('$a', 'return $a["slug"];'),
							$this->db->like('slug', $slug_candidate)->select('slug')->get('member')->result_array()
						);
						$slug_post = slug_uniqify($slug_candidate, $possible_conflicts);
						
						//Insert member
						$info = array(
							'slug' => $slug_post,
							'fullname' => $fullname,
							'first_name' => $first_name,
							'last_name' => $last_name,
							'phone' => $phone,
							'email' => $email,
							'address' => $address,
							'password' => $this->bcrypt->hash_password($password),
							'avatar' => 'avatar_default.png',
							'level' => 'user',
							'status' => 'active',
							'agent' => $this->_selfAgent(),
							'created' => date('Y-m-d H:i:s'),
							'updated' => date('Y-m-d H:i:s'),
							'created_by' => 0,
							'updated_by' => 0,
							'verify_key' => NULL,
						);
						
						if($this->db->insert('member', $info)){
							$memberID = $this->db->insert_id();
							
							// Send Email
							$message = array(
								'title' => 'Registration Successful',
								'body' => '<p><b>Hi '.$fullname.',</b></p>
									<p>Thank you for joining us.</p>
									<p>TETAPBISA.ID by Bir Bintang Indonesia is your guide to enjoy Bali at its finest without worrying about anything but where to go next. Together with Bintang, #TetapBisa!</p>
								<br/><br/>
								<p><small>If you never feel registered, please ignore this email.</small></p>',
							);
							$this->_sendtoEmail('contact_tracing', $email, $message);
						}else{
							$this->response('error', msg('ObjectCannotBeSaved'));
						}
					}else{
						$memberID = $check->member_id;
					}
				}else{
					$memberID = $this->isloginaccount['member_id'];
				}
				
				$info = array(
					'bar_id' => $bar_id,
					'fullname' => $fullname,
					'address' => $address,
					'phone' => $phone,
					'member_id' => $memberID,
					'safety_id' => 0, //implode(',', $safety_id)
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'agent' => $this->_selfAgent(),
				);
				
				if($this->db->insert('contact_tracing', $info)){
					//Update profile member 
					if($this->isloginaccount){
						$this->db->update('member', array('fullname' => $fullname, 'address' => $address, 'phone' => $phone), array('member_id' => $this->isloginaccount['member_id']));
					}
					
					$this->response('success', msg('system_success_tracing'));
				}else{
					$this->response('error', msg('ObjectCannotBeSaved'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function us()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_rules('bar_id', 'bar_id', 'trim|required');
			$this->form_validation->set_rules('topic_id', 'topic_id', 'trim|required');
			$this->form_validation->set_rules('question', 'question', 'trim|required|min_length[30]');
			
			if(!$this->isloginaccount){
				$this->form_validation->set_rules('fullname', 'fullname', 'trim|required|min_length[3]|max_length[100]');
				$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			}
			
			if ($this->form_validation->run()==TRUE) {
				$bar_id = $this->input->input_stream('bar_id');
				$topic_id = $this->input->input_stream('topic_id');
				$question = $this->input->input_stream('question');
				$fullname = ($this->isloginaccount ? $this->isloginaccount['fullname'] : $this->input->input_stream('fullname'));
				$email = ($this->isloginaccount ? $this->isloginaccount['email'] : $this->input->input_stream('email'));
				
				//Non Symbolic email
				if(strpos($email, '+') == TRUE){
					$this->response('error', msg('system_email_invalid', 'email'));
				}
				
				//Blacklist email
				$blacklist = $this->db->limit(1)->get_where('blacklist', array('domain' => substr(strrchr($email, "@"), 1)))->row();
				if($blacklist){
					$this->response('error', msg('system_email_blacklist', 'email'));
				}
				
				//Check domain
				list($user_email, $domain_email) = explode('@', $email);
				if(checkdnsrr($domain_email) === FALSE){
					$this->response('error', msg('system_email_inactive', 'email'));
				}
				
				//Emoticon encode
				$question = $this->emoji->encode($question);
				
				$info = array(
					'bar_id' => $bar_id,
					'fullname' => $fullname,
					'email' => $email,
					'topic_id' => $topic_id,
					'question' => $question,
					'status' => 'waiting',
					'member_id' => ($this->isloginaccount ? $this->isloginaccount['member_id'] : NULL),
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'agent' => $this->_selfAgent(),
				);
				
				if($this->db->insert('contact_us', $info)){
					$this->response('success', msg('system_success_contact'));
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
