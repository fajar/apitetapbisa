<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Screen extends ST_Controller {
	
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
	
	public function asset()
	{
		$type = $this->input->get('type');
		
		$data = array();
		switch($type){
			case 'home':
				$featured = array(
					array(
						'action' => array(
							'type' => 'safety-protocol',
							'value' => ''
						),
						'cover' => s3content('featured/home_cta_3.png'),
					),
					array(
						'action' => array(
							'type' => 'bar',
							'value' => ''
						),
						'cover' => s3content('featured/home_cta_2.png'),
					),
					array(
						'action' => array(
							'type' => 'auth',
							'value' => 'register'
						),
						'cover' => s3content('featured/home_cta_1.png'),
					),
				);
				
				$data = array(
					'why_bintang_barcode' => $featured,
					'home_bars' => array(
						'cover' => asset('web/image/home_about_bali.png'),
						'action' => array(
							'type' => 'bar',
							'value' => ''
						)
					),
					'bali_about' => array(
						'cover' => asset('web/image/about_bali.jpg'),
						'action' => array(
							'type' => 'about-bali',
							'value' => ''
						)
					)
				);
				
				break;
			case 'safety-protocol':
				// Photo product 
				$this->load->helper('directory');
				$supported_format = array('gif','jpg','jpeg','png');
				$productBars = directory_map('./storage/upload/product/', 1);
				$product = array();
				foreach($productBars AS $red){
					$ext = strtolower(pathinfo($red, PATHINFO_EXTENSION));
					if (in_array($ext, $supported_format)){
						$title = ucwords(str_replace(array($ext, '_', '.'), array('', ' ', ''), $red));
						$product[] = array(
							'title' => $title,
							'image' => s3content('upload/product/'.$red),
						);
					}
				}
				
				//Photo gallery 
				$galleryBars = directory_map('./storage/upload/gallery/', 1);
				$gallery = array();
				foreach($galleryBars AS $red){
					$ext = strtolower(pathinfo($red, PATHINFO_EXTENSION));
					if (in_array($ext, $supported_format)){
						$gallery[] = array(
							'title' => translation('safety_protocol'),
							'image' => s3content('upload/gallery/'.$red),
						);
					}
				}
				
				if($this->lang == 'en'){
					$healthprotocol = array(
						array(
							'title' => 'Education about COVID-19 health protocols with Bali Tourism Board',
							'cover' => s3content('healthprotocol/healthprotocol_1.png'),
						),
						array(
							'title' => 'Outlet verification by Bali Tourism Board',
							'cover' => s3content('healthprotocol/healthprotocol_2.png'),
						),
						array(
							'title' => 'Certification & publication',
							'cover' => s3content('healthprotocol/healthprotocol_3.png'),
						),
						array(
							'title' => 'Regular outlet visit to monitor health protocol implementation',
							'cover' => s3content('healthprotocol/healthprotocol_4.png'),
						),
					);
				}else{
					$healthprotocol = array(
						array(
							'title' => 'Edukasi tentang Protokol Kesehatan COVID-19 dari Dinas Pariwisata Bali',
							'cover' => s3content('healthprotocol/healthprotocol_1.png'),
						),
						array(
							'title' => 'Verifikasi outlet dari Dinas Pariwisata Bali',
							'cover' => s3content('healthprotocol/healthprotocol_2.png'),
						),
						array(
							'title' => 'Sertifikasi & publikasi',
							'cover' => s3content('healthprotocol/healthprotocol_3.png'),
						),
						array(
							'title' => 'Kunjungan ke outlet secara berkala untuk memantau penerapan Protokol Kesehatan',
							'cover' => s3content('healthprotocol/healthprotocol_4.png'),
						),
					);
				}
				
				if($this->lang == 'en'){ 
					$video_link = 'Safetyprotocol-Bintang.mp4';
				}else{
					$video_link = 'BintangSafetyProtocolVideoInd.mp4';
				}
				
				$data = array(
					'video' => s3content('upload/'.$video_link),
					'product' => $product,
					'health_protocol' => $healthprotocol,
				);
				
				break;
			default:
		}
		
		$this->response('success', $data);
	}
}
