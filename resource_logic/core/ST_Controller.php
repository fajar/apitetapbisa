<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ST_Controller extends CI_Controller {
	
	var $isheaderallowed;
	
	public function __construct() {
		parent::__construct();
		$this->storagepath = realpath(APPPATH . '../storage');
		
		//Auth
		$this->isheaderallowed = $this->_authCheck();
	}
	
	//Auth API
	private function _authCheck()
	{
		$authorization = $this->input->get_request_header('Authorization');
		if(! $authorization){
			$this->response('error', msg('NotNull', 'Authorization'));
		}
		
		list($appid, $serverkey) = explode(':', base64_decode(trim(str_replace('Basic', '', $authorization))));
		$mobile_id = $this->input->get_request_header('DeviceID', TRUE);
		$mobile_session = $this->input->get_request_header('MobileSession', TRUE);
		$push_token = $this->input->get_request_header('PushToken', TRUE);
		$language = strtolower($this->input->get_request_header('Language', TRUE));
		
		//Validation
		if(!$appid){
			$this->response('error', msg('NotNull', 'Username'));
		}elseif(!$serverkey){
			$this->response('error', msg('NotNull', 'Password'));
		}elseif(!$push_token){
			$this->response('error', msg('NotNull', 'PushToken'));
		}elseif(!$mobile_id){
			$this->response('error', msg('NotNull', 'DeviceID'));
		}elseif(!$language){
			$this->response('error', msg('NotNull', 'Language'));
		}
		
		// Language 
		if(!in_array($language, array('id','en'))){
			$language = 'en';
		}
		
		//Check DB
		$check = $this->db->limit(1)->get_where('api_connect', array('app_id' => $appid, 'server_key' => $serverkey))->row();
		if(! $check){
			$this->response('error', msg('ObjectNotFound', 'Authorization'));
		}
		
		//Flag status
		if($check->published == 'unpublish'){
			$this->response('error', msg('InsufficientPermissions', 'Authorization'));
		}
		
		//Log
		$this->db->insert('log_api', array(
			'info' => json_encode($this->input->request_headers()),
			'created' => date('Y-m-d H:i:s'),
			'agent' => json_encode($this->agent)
		));
		
		//Set master data
		return array(
			'serverkey' => $serverkey,
			'app_id' => $appid,
			'push_token' => $push_token,
			'serial_number' => $check->serial_number,
			'mobile_session' => ($mobile_session ? $mobile_session : NULL),
			'mobile_device' => $check->device,
			'package_name' => $check->package_name,
			'mobile_id' => $mobile_id,
			'build_version' => $check->build_version,
			'download_url' => $check->download_url,
			'language' => $language,
		);
	}
	
	//Data session login
	public function _loginCheck($checkOnly = false)
	{
		//Validation
		if(!$this->isheaderallowed['mobile_session']){
			$this->response('error', msg('NotNull', 'MobileSession', 'login'));
		}
		
		//Check DB
		$access = array(
			'member_mobile_access.package_name' => $this->isheaderallowed['package_name'],
			'member_mobile_access.mobile_session' => $this->isheaderallowed['mobile_session']
		);
		
		$session = $this->db
			->select('member.fullname, member.first_name, member.last_name, member.email, member.phone, member.level, member.status, member.address')
			->select('member_mobile_access.*')
			->join('member', 'member.member_id = member_mobile_access.member_id', 'inner')
			->limit(1)
			->get_where('member_mobile_access', $access)
			->row_array();
		
		if(! empty($session)){
			//Status banned
			if($session['status'] == 'banned'){
				if($checkOnly){
					return FALSE;
				}else{
					$this->response('error', msg('InsufficientPermissions', '', 'logout'));
				}
			}
			
			if($session['mobile_device'] != $this->isheaderallowed['mobile_device']){
				if($checkOnly){
					return FALSE;
				}else{
					$this->response('error', msg('InvalidState', 'MobileSession', 'logout'));
				}
			}elseif($session['mobile_id'] != $this->isheaderallowed['mobile_id']){
				if($checkOnly){
					return FALSE;
				}else{
					$this->response('error', msg('InvalidState', 'DeviceID', 'logout'));
				}
			}else{
				//Save push token every hit endpoint
				$push = array(
					'push_token' => $this->isheaderallowed['push_token'],
					'mobile_id' => $this->isheaderallowed['mobile_id'],
				);
				
				if($this->db->update('member_mobile_access', $push, array('node_id' => $session['node_id']))){
					//Allowed access
					return array_merge($this->isheaderallowed, $session);
				}else{
					if($checkOnly){
						return FALSE;
					}else{
						$this->response('error', msg('ObjectCannotBeSaved', '', 'login'));
					}
				}
			}
		}else{
			if($checkOnly){
				return FALSE;
			}else{
				$this->response('error', msg('InsufficientPermissions', 'MobileSession', 'login'));
			}
		}
	}
	
	public function response($status = 'error', $data = array(), $info = array(), $header = 200)
	{
		$result = array(
			'status' => $status,
			'data' => ($status == 'error' ? null : (empty($data) ? null : (isset($data['message']) ? NULL : $data))),
			'info' => (empty($info) ? (isset($data['message']) ? $data : NULL) : $info),
		);
		
		$this->output
			->set_status_header($header)
			->enable_profiler(FALSE)
			->set_content_type('application/json', 'utf-8')
			->set_output($this->_jsonPretty(json_encode($result)))
			->_display();

		exit;
	}
	
	private function _jsonPretty($json, $istr='  ')
	{
		$result = '';
		for($p=$q=$i=0; isset($json[$p]); $p++)
		{
			$json[$p] == '"' && ($p>0?$json[$p-1]:'') != '\\' && $q=!$q;
			if(!$q && strchr(" \t\n\r", $json[$p])){continue;}
			if(strchr('}]', $json[$p]) && !$q && $i--)
			{
				strchr('{[', $json[$p-1]) || $result .= "\n".str_repeat($istr, $i);
			}
			$result .= $json[$p];
			if(strchr(',{[', $json[$p]) && !$q)
			{
				$i += strchr('{[', $json[$p])===FALSE?0:1;
				strchr('}]', $json[$p+1]) || $result .= "\n".str_repeat($istr, $i);
			}
		}
		return $result;
	}
	
	function _isJson($json = null, $return_data = false)
	{
		if($json){
			$data = (array)json_decode($json);
			return ((json_last_error() == JSON_ERROR_NONE) ? ($return_data ? $data: TRUE):FALSE);
		}
		
		return false;
	}
	
	function _selfAgent()
	{
		return json_encode(array(
			'ip' => $this->input->ip_address(),
			'agents' => $this->agent->agent,
			'platform' => $this->agent->platform(),
			'version' => $this->agent->version(),
			'mobile' => $this->agent->mobile(),
			'robot' => $this->agent->robot(),
			'browser' => $this->agent->browser(),
			'languages' => $this->agent->languages(),
		));
	}
	
	function _saveLog($category = null, $info = null)
	{
		if($category == NULL || $info == NULL){
			return TRUE;
		}
		
		$data = array(
			'category' => $category,
			'info' => $info,
			'created' => date('Y-m-d H:i:s'),
			'ip' => $this->input->ip_address(),
			'agent' => $this->_selfAgent(),
		);
		
		$this->db->insert('log_error', $data);
		
		return TRUE;
	}
	
	function _sendtoEmail($category = 'all', $email = null, $info = array(), $attach = array(), $platform = null)
	{
		if($info){
			$title = (isset($info['title']) ? $info['title'] : WEBTITLE);
			$body = $this->load->view('email', $info, TRUE);
			
			// Send Email
			$this->load->library('email');

			$this->email->initialize(array(
				'useragent' => WEBTITLE,
				'protocol' => 'smtp',
				'smtp_crypto' => 'tls',
				'smtp_host' => 'smtp.mandrillapp.com',
				'smtp_user' => 'Tetap Bisa',
				'smtp_pass' => '3jaAWGx15Fr4w_dV5cHEMQ',
				'smtp_port' => 587,
				'mailtype' => 'html',
				'charset' => 'utf-8',
				'priority' => 1,
				'wordwrap' => TRUE,
				'crlf' => "\r\n",
				'newline' => "\r\n"
			));

			$this->email->from('noreply@tetapbisa.id', WEBTITLE);
			$this->email->to($email);
			$this->email->subject($title);
			
			if($attach){
				foreach($attach AS $red){
					$this->email->attach($red);
				}
			}
			
			$this->email->message($body);
			if(! $this->email->send()){
				$this->_saveLog($category, json_encode($this->email->print_debugger()));
			}
			
			//Push notification
			if(!is_array($email) && $platform == 'firebase'){
				//Send to firebase
				$this->load->library('firebase');
				$this->firebase->send($email, array(
					'title' => (isset($info['title']) ? $info['title'] : WEBTITLE),
					'body' => (isset($info['body']) ? character_limiter(clean_html($info['body']), 160) : WEBDESC),
					'image' => null,
					'cta_category' => 'notification',
					'cta_value' => null,
					'counter' => 1,
					'analytics_label' => 'notification',
					'description' => (isset($info['body']) ? $info['body'] : WEBDESC),
					'icon' => null,
					'video' => null,
					'shape' => 'activity',
				), 'email');
			}
		}

		return TRUE;
	}
}

/* End of file core */