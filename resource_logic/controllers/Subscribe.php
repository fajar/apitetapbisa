<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscribe extends ST_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		//hash password
		$this->load->library('bcrypt');
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function newsletter()
	{
		if($this->input->input_stream()){
			$this->form_validation->set_data($this->input->input_stream());
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|max_length[100]|valid_email');
			
			if ($this->form_validation->run()==TRUE) {
				$email = $this->input->input_stream('email');
				
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
				
				$check = $this->db->limit(1)->get_where('subscribe', array('email' => $email))->row();
				if($check){
					$this->response('success', msg('system_subscribe_thanks'));
				}else{
					$info = array(
						'email' => $email,
						'status' => 'new',
						'created' => date('Y-m-d H:i:s'),
						'updated' => date('Y-m-d H:i:s'),
						'agent' => $this->_selfAgent(),
					);
					
					if($this->db->insert('subscribe', $info)){
						$this->response('success', msg('system_subscribe_thanks'));
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
}
