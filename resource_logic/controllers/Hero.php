<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hero extends ST_Controller {
	
	var $lang;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->lang = $this->isheaderallowed['language'];
	}
	
	public function index()
	{
		$this->response('error', msg('ObjectNotFound'));
	}
	
	public function slider()
	{
		$data = $this->db
			->order_by('created', 'DESC')
			->get_where('hero', array('published' => 'publish'))
			->result();

		$json_data = array();
		foreach($data as $pro){
			//Convert to activity 
			$action = array(
				'type' => '',
				'value' => '',
			);
			
			if($pro->url){
				$action = array(
					'type' => 'url',
					'value' => $pro->url,
				);
				
				$parse = parse_url($pro->url);
				if($parse){
					$path = array_values(array_filter(array_map('trim', explode('/', $parse['path']))));
					if($path){
						$action = array(
							'type' => (isset($path[0]) ? $path[0] : ''),
							'value' => (isset($path[1]) ? $path[1] : ''),
						);
					}
				}
			}
			
			$json_data[] = array(
				'title' => $pro->title,
				'cover' => s3content('hero/'.$pro->cover_mobile),
				'action' => $action,
			);
		}
		
		$this->response('success', $json_data);
	}
}
