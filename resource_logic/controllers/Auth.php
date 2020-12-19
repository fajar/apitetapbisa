<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends ST_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		//Load models
		$this->load->model(array('member_mod', 'master_mod'));
		$this->load->library('bcrypt');
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function register()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[20]'); 
			$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|matches[password]');
			$this->form_validation->set_rules('first_name', 'First name', 'trim|required|min_length[3]|max_length[100]');
			$this->form_validation->set_rules('last_name', 'Last name', 'trim|required|min_length[3]|max_length[100]');
			$this->form_validation->set_rules('address', 'Address', 'trim|required');
			$this->form_validation->set_rules('phone', 'phone', 'trim|required|min_length[10]|max_length[15]');
			
			if ($this->form_validation->run()==TRUE) {
				$email = strtolower($this->input->input_stream('email'));
				$password = $this->input->input_stream('password_confirmation');
				$first_name = $this->input->input_stream('first_name');
				$last_name = $this->input->input_stream('last_name');
				$address = $this->input->input_stream('address');
				$phone = $this->input->input_stream('phone');
				$fullname = $first_name.' '.$last_name;
				
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
				
				$check = $this->member_mod->detail_member($email, 'email');
				if($check){
					$this->response('error', msg('registerError', 'email', 'login'));
				}else{
					//Slug Title for URL
					$slug_candidate = slug(clean_html($fullname));
					$slug_candidate = rtrim($slug_candidate, '-0123456789');
					$possible_conflicts = array_map(
						function($a){ return $a['slug']; },
						$this->db->like('slug', $slug_candidate)->select('slug')->get('member')->result_array()
					);
					$slug_post = slug_uniqify($slug_candidate, $possible_conflicts);
					
					//Insert member
					$verify_key = genToken(67);
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
						'verify_key' => $verify_key,
					);
					
					if($this->db->insert('member', $info)){
						// Send Email
						$message = array(
							'title' => 'Registration Successful',
							'body' => '<p><b>Hi '.$fullname.',</b></p>
								<p>Thank you for joining us.</p>
								<p>TETAPBISA.ID by Bir Bintang Indonesia is your guide to enjoy Bali at its finest without worrying about anything but where to go next. Together with Bintang, #TetapBisa!</p>
							<br/><br/>
							<p><small>If you never feel registered, please ignore this email.</small></p>',
						);
						$this->_sendtoEmail('register', $email, $message);
						
						$check = $this->member_mod->detail_member($email, 'email');
						
						//Create session login
						$data = $this->_sessionLogin($check);
						if($data){
							$this->response('success', $data, msg('LoginSuccess'));
						}else{
							$this->response('error', msg('ObjectCannotBeSaved', 'session'));
						}
					}else{
						$this->response('error', msg('ObjectCannotBeSaved'));
					}
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function login()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$email = $this->input->input_stream('email');
				$password = $this->input->input_stream('password');
				
				//Non Symbolic email
				if(strpos($email, '+') == TRUE){
					$this->response('error', msg('system_email_invalid', 'email'));
				}
				
				//Check domain
				list($user_email, $domain_email) = explode('@', $email);
				if(checkdnsrr($domain_email) === FALSE){
					$this->response('error', msg('system_email_inactive', 'email'));
				}
				
				$check = $this->member_mod->detail_member($email, 'email');
				if($check){
					//Status
					if($check->status == 'banned'){
						$this->response('error', msg('InsufficientPermissions'));
					}
					
					//check hash password
					if($this->bcrypt->check_password($password, $check->password) === FALSE){
						$this->response('error', msg('LoginError', 'password'));
					}
					
					//Create session login
					$data = $this->_sessionLogin($check);
					if($data){
						$this->response('success', $data, msg('LoginSuccess'));
					}else{
						$this->response('error', msg('ObjectCannotBeSaved', 'session'));
					}
				}else{
					$this->response('error', msg('LoginError', 'email'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	private function _sessionLogin($member = null)
	{
		if(! $member){ return FALSE; }
		
		//Create sesssion
		$mobile_session = $this->bcrypt->hash_password($this->isheaderallowed['serverkey'].''.$this->isheaderallowed['push_token'].''. $member->member_id);
		$info = array(
			'package_name' => $this->isheaderallowed['package_name'],
			'push_token' => $this->isheaderallowed['push_token'],
			'mobile_session' => $mobile_session,
			'mobile_device' => $this->isheaderallowed['mobile_device'],
			'mobile_id' => $this->isheaderallowed['mobile_id'],
			'updated' => date('Y-m-d H:i:s'),
			'agent' => $this->_selfAgent(),
		);
		
		//Check session 
		$session = $this->member_mod->check_mobile_access($this->isheaderallowed['serial_number'], $member->member_id);
		if($session){
			if($this->db->update('member_mobile_access', $info, array('node_id' => $session->node_id))){
				$new_register = ($session->new_register == 'true' ? TRUE : FALSE);
			}else{
				return FALSE;
			}
		}else{
			$info['member_id'] = $member->member_id;
			$info['created'] = date('Y-m-d H:i:s');
			$info['new_register'] = 'true';
			
			if($this->db->insert('member_mobile_access', $info)){
				$new_register = TRUE;
			}else{
				return FALSE;
			}
		}
		
		$data = (array)$this->member_mod->detail_member($member->member_id, 'member_id', null, 'public');
		if($data){
			$data['mobile_session'] = $mobile_session;
			$data['package_name'] = $this->isheaderallowed['package_name'];
			$data['language'] = $this->isheaderallowed['language'];
			$data['new_register'] = $new_register;
			
			return $data;
		}else{
			return FALSE;
		}
	}
	
	public function logout()
	{
		//Login check
		$this->isloginaccount = $this->_loginCheck();
		
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$email = $this->input->input_stream('email');
				
				//Non Symbolic email
				if(strpos($email, '+') == TRUE){
					$this->response('error', msg('system_email_invalid', 'email'));
				}
				
				//Check domain
				list($user_email, $domain_email) = explode('@', $email);
				if(checkdnsrr($domain_email) === FALSE){
					$this->response('error', msg('system_email_inactive', 'email'));
				}
				
				$check = $this->member_mod->detail_member($email, 'email');
				if(! $check){
					$this->response('error', msg('MissingId', 'email'));
				}
				
				if($this->isheaderallowed['mobile_session']){
					//Session login
					$info = array(
						'mobile_session' => NULL,
						'mobile_device' => NULL,
						'mobile_id' => NULL,
						'agent' => $this->_selfAgent(),
						'updated' => date('Y-m-d H:i:s'),
					);
					
					//Reset session
					if($this->db->update('member_mobile_access', $info, array('package_name' => $this->isheaderallowed['package_name'], 'member_id' => $check->member_id))){
						$this->response('success', msg('SuccessAction'));
					}else{
						$this->response('error', msg('ObjectCannotBeSaved'));
					}
				}else{
					$this->response('error', msg('InsufficientPermissions'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function forgot_password()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$email = $this->input->input_stream('email');
				
				//Non Symbolic email
				if(strpos($email, '+') == TRUE){
					$this->response('error', msg('system_email_invalid', 'email'));
				}
				
				//Check domain
				list($user_email, $domain_email) = explode('@', $email);
				if(checkdnsrr($domain_email) === FALSE){
					$this->response('error', msg('system_email_inactive', 'email'));
				}
				
				$check = $this->member_mod->detail_member($email, 'email');
				if(! $check){
					$this->response('error', msg('MissingId', 'email'));
				}
				
				//Status
				if($check->status == 'banned'){
					$this->response('error', msg('InsufficientPermissions'));
				}
				
				//Verify key
				$verify_key = digitToken();
				
				//Update token
				if($this->db->update('member', array('verify_key' => $verify_key), array('member_id' => $check->member_id))){
					//send email
					$info = array(
						'title' => 'Forgot Password',
						'body' => '<p><b>Hi '.$check->fullname.',</b></p>
							<p>Request forgot password, your verify code is :</p>
							<br/><br/><h2>'.$verify_key.'</h2>
							<br/><br/><p><small>If you never request a forgot password, ignore this email. You can still connect to your old password.</small></p>',
					);
					
					$this->_sendtoEmail('forgot_password', $check->email, $info);
					
					$this->response('success', msg('SuccessAction'));
				}else{
					$this->response('error', msg('ObjectCannotBeSaved'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
	
	public function verify_password()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('verify_key', 'Verify Key', 'trim|required'); 
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[20]'); 
			$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|matches[password]');
			
			if($this->form_validation->run()==TRUE){
				//Variable
				$verify_key = $this->input->input_stream('verify_key');
				$email = $this->input->input_stream('email');
				$password = $this->input->input_stream('password_confirmation');
				
				//Non Symbolic email
				if(strpos($email, '+') == TRUE){
					$this->response('error', msg('system_email_invalid', 'email'));
				}
				
				//Check domain
				list($user_email, $domain_email) = explode('@', $email);
				if(checkdnsrr($domain_email) === FALSE){
					$this->response('error', msg('system_email_inactive', 'email'));
				}
				
				//Check 
				$check = $this->member_mod->detail_verify('email', $email, $verify_key);
				if($check){
					//Status
					if($check->status == 'banned'){
						$this->response('error', msg('InsufficientPermissions'));
					}
					
					$info = array(
						'password' => $this->bcrypt->hash_password($password),
						'verify_key' => NULL,
						'updated' => date('Y-m-d H:i:s'),
						'updated_by' => $check->member_id,
					);
					
					if($this->db->update('member', $info, array('member_id' => $check->member_id))){
						//Create session login
						$data = $this->_sessionLogin($check);
						if($data){
							$this->response('success', $data, msg('LoginSuccess'));
						}else{
							$this->response('error', msg('ObjectCannotBeSaved', 'session'));
						}
					}else{
						$this->response('error', msg('SystemError'));
					}
				}else{
					$this->response('error', msg('ObjectNotFound', 'verify_key'));
				}
			}else{
				$this->response('error', msg(implode(' ', $this->form_validation->error_array()), implode(',', array_keys($this->form_validation->error_array()))));
			}
		}
		
		$this->response('error', msg('InvalidMethod'));
	}
}
