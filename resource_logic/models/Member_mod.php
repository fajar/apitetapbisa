<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member_mod extends CI_Model {

	function __construct(){
		parent::__construct();
	}
	
	function detail_verify($type = 'member_id', $cid = null, $kid = null)
	{
		if(! $cid){ return FALSE; }
		if(! $kid){ return FALSE; }
		
		return $this->db->limit(1)->get_where('member', array($type => $cid, 'verify_key' => $kid))->row();
	}
	
	function detail_member($id = null, $type = 'member_id', $provider = null, $model = null){
		if(! $id){ return FALSE; }
		if(! in_array($type, array('member_id', 'email'))){ return FALSE; }
		
		if($model == 'public'){
			$this->db->select('member.member_id, member.fullname, member.first_name, member.last_name, member.email, member.address, member.phone, member.avatar, member.level, member.status, member.created, member.updated');
		}else{
			$this->db->select('member.*');
		}
		
		$data = $this->db
			->where(array('member.'.$type => $id))
			->limit(1)
			->get('member')
			->row();
			
		if($data){
			$data->avatar = foto('avatar/'. $data->avatar, 200, 200);
			
			// Have bar
			$is_bar = $this->db
				->where(array('owner_id' => $data->member_id))
				->limit(1)
				->get('bar')
				->row();
				
			$data->have_bar = ($is_bar ? TRUE : FALSE);
			
			if($data->phone){
				$data->phone = preg_replace('/^0/', '+62', $data->phone);
			}
		}
		
		return $data;
	}
	
	function check_mobile_access($serial_number = null, $member_id = null){
		if(!$serial_number){ return FALSE; }
		if(!$member_id){ return FALSE; }
		
		return $this->db
			->select('member_mobile_access.*')
			->select('api_connect.serial_number')
			->join('api_connect', 'api_connect.package_name = member_mobile_access.package_name', 'inner')
			->limit(1)
			->get_where('member_mobile_access', array('api_connect.serial_number' => $serial_number, 'member_mobile_access.member_id' => $member_id))
			->row();
	}
}