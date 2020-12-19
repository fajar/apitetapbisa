<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Firebase {

	public function __construct()
    {
		$this->CI =& get_instance();
    }
	
	//Target: token, topic
	//Params: title, body, image, cta_category, cta_value, counter, analytics_label
	function cloudmessaging($target = 'token', $devices = null, $params = array())
	{
		if($devices){
			if(! $params){
				return TRUE;
			}
			
			$url = 'https://fcm.googleapis.com/fcm/send';
			
			//Params 
			$field = array(
				'collapse_key' => $params['cta_category'],
				'priority' => 'high',
				'mutable_content' => TRUE,
				'notification' => array(
					'title' => character_limiter(clean_html($params['title']), 160),
					'body' => character_limiter(clean_html($params['body']), 160),
					'icon' => 'default',
					'sound' => 'default',
					'click_action' => $params['cta_category'],
					'tag' => $params['cta_category'],
					'android_channel_id' => $params['cta_category'],
					'badge' => $params['counter']
				),
				'data' => array(
					'title' => character_limiter(clean_html($params['title']), 160),
					'body' => character_limiter(clean_html($params['body']), 160),
					'cta_category' => $params['cta_category'],
					'cta_value' => $params['cta_value'],
					'badge' => $params['counter'],
					'created' => date('Y-m-d H:i:s'),
				),
			);
			
			//Conditional item
			if(isset($params['image']) == TRUE && $params['image'] != ''){
				$field['data']['image'] = $params['image'];
			}
			
			switch($target){
				case 'topic':
					$field['to'] = '/topics/'.$devices;
					break;
				default:
					$tokenID = array_values(array_filter(array_unique($devices)));
					if(count($tokenID) <= 1){
						$field['to'] = $tokenID[0];
					}else{
						$field['registration_ids'] = $tokenID;
					}
			}
			
			$headers = array(
				'Content-Type: application/json',
				'Authorization: key='.FCM_SERVERKEY
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($field));
			$result = curl_exec($ch);
			if ($result === FALSE) {
				$data = array(
					'category' => 'fcm_failed',
					'info' => json_encode(curl_error($ch)),
					'created' => date('Y-m-d H:i:s'),
					'ip' => $this->CI->input->ip_address(),
					'agent' => json_encode(array(
						'referrer' => $this->CI->agent->referrer(),
						'agents' => $this->CI->agent->agent,
						'platform' => $this->CI->agent->platform(),
						'version' => $this->CI->agent->version(),
						'mobile' => $this->CI->agent->mobile(),
						'robot' => $this->CI->agent->robot(),
						'browser' => $this->CI->agent->browser(),
						'languages' => $this->CI->agent->languages(),
					)),
				);
				$this->CI->db->insert('log_error', $data);
			}
			curl_close($ch);
			
			//Get icon
			$iconSet = s3content('notification_icon/'.'default.png');
			
			//Save to table notification
			if($target == 'topic'){
				$info = array(
					'last_recipient' => 0,
					'title' => (isset($params['title']) ? $params['title'] : '-'),
					'subtitle' => (isset($params['body']) ? $params['body'] : '-'),
					'description' => (isset($params['description']) ? $params['description'] : '-'),
					'icon' => $iconSet,
					'cover' => (isset($params['image']) ? $params['image'] : null),
					'video' => (isset($params['video']) ? $params['video'] : null),
					'cta_category' => (isset($params['cta_category']) ? $params['cta_category'] : 'notification'),
					'cta_value' => (isset($params['cta_value']) ? $params['cta_value'] : null),
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'is_read' => 'false',
				);
				
				$this->CI->db->insert('notification_schedule', $info);
			}else{
				$member = $this->CI->db
					->where_in('push_token', $devices)
					->limit(count($devices))
					->group_by('member_id')
					->get('member_mobile_access')
					->result();
					
				if(! $member){
					return TRUE;
				}
				
				$info = array();
				foreach($member AS $red){
					$info[] = array(
						'member_id' => $red->member_id,
						'title' => (isset($params['title']) ? $params['title'] : '-'),
						'subtitle' => (isset($params['body']) ? $params['body'] : '-'),
						'description' => (isset($params['description']) ? $params['description'] : '-'),
						'icon' => $iconSet,
						'cover' => (isset($params['image']) ? $params['image'] : null),
						'video' => (isset($params['video']) ? $params['video'] : null),
						'cta_category' => (isset($params['cta_category']) ? $params['cta_category'] : 'notification'),
						'cta_value' => (isset($params['cta_value']) ? $params['cta_value'] : null),
						'created' => date('Y-m-d H:i:s'),
						'updated' => date('Y-m-d H:i:s'),
						'is_read' => 'false',
					);
				}
				
				$this->CI->db->insert_batch('notification', $info);
			}
		}
		
		return TRUE;
	}
	
	function send($memberID = null, $params = array(), $type= 'id')
	{
		if(! $memberID){ return TRUE; }
		
		if($params){
			//Member token 
			if($type == 'email'){
				$mobile = $this->CI->db
					->select('member_mobile_access.push_token')
					->join('member_mobile_access', 'member_mobile_access.member_id = member.member_id', 'inner')
					->order_by('member_mobile_access.updated', 'DESC')
					->limit(1)
					->get_where('member', array('member.email' => $memberID))
					->row();
			}else{
				$mobile = $this->CI->db
					->order_by('updated', 'DESC')
					->limit(1)
					->get_where('member_mobile_access', array('member_id' => $memberID))
					->row();
			}
				
			if($mobile){
				if(strlen($mobile->push_token) > 50){
					//Send to firebase
					$this->cloudmessaging('token', array($mobile->push_token), $params);
				}
			}
		}
		
		return TRUE;
	}
}

/* End of file Firebase.php */
/* Location: ./application/libraries/Firebase.php */