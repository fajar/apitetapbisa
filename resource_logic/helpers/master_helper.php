<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('enc_dec'))
{  
  function enc_dec($action='e', $string= null) {
		if(empty($action)){ return false;}
		if(empty($string)){ return false;}
		$output = false;

		$encrypt_method = 'AES-256-CBC';
		$secret_key = 'cc4a223a91ee68b8118258b703ef7de0d5ae67a9';
		$secret_iv = 'b7aa73f54aca75a2945d3693977d2960241be59a';
		$key = hash('sha256', $secret_key);
		$iv = substr(hash('sha256', $secret_iv), 29, 16);

		if( $action == 'e' ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		}elseif( $action == 'd' ){
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}
		return $output;
	}
}

if ( ! function_exists('weburl'))
{
    function weburl($url = '') {
        if(ENVIRONMENT != 'production'){
            return prep_url('https://stag.tetapbisa.id/'.$url);
        }else{
            return prep_url('https://www.tetapbisa.id/'.$url);
        }
    }
}

if ( ! function_exists('s3content'))
{
	function s3content($url = null) {
		if(ENVIRONMENT != 'production'){
			return prep_url('https://asset.tetapbisa.id/stag/'.$url);
		}else{
			return prep_url('https://asset.tetapbisa.id/'.$url);
		}
	}
}

if ( ! function_exists('asset'))
{
	function asset($url = null) {
		return prep_url('https://www.tetapbisa.id/static/'.$url);
	}
}

if ( ! function_exists('foto'))
{
	function foto($url = null, $x = 0, $y = 0, $default = 'default.png') {
		//Init
		$CI =& get_instance();
		$CI->load->library(array('image_lib'));
		
		//default cropping size
		if(is_numeric($x) == FALSE AND is_numeric($y) == FALSE){
			return s3content($default);
		}
		
		//check dir thumbnail
		$size = $x.'_'.$y;
		$thumbnail = APPPATH .'../storage/resize/'.$size;
		if(is_dir($thumbnail) == FALSE) {
			mkdir($thumbnail, 0755, true);
			chmod($thumbnail, 0755);
			chown($thumbnail, 'www-data');
			chgrp($thumbnail, 'www-data');
		}
		
		//check file in original dir
		$splitpath = explode('/', $url);
		$data = APPPATH.'../storage/'.$url;
		if(file_exists($data)){
			$img = end($splitpath);
			$sourceimg = $url;
		}else{
			$def = explode('/',$default);
			$img = end($def);
			$sourceimg = $default;
			
			$data = APPPATH.'../storage/'.$default;
		}
		
		//check thumbnail generated
		$gambar = $thumbnail.'/'.$img;
		if(is_file($gambar) == false){
			//croping
			$x_img = ($x ? $x : 200);
			$y_img = ($y ? $y : 200);
			list($img_width, $img_height) = getimagesize(APPPATH .'../storage/'. $sourceimg);
			
			//Resize
			$image_config['image_library'] = 'gd2';
			$image_config['source_image'] = APPPATH .'../storage/'. $sourceimg;
			$image_config['maintain_ratio'] = TRUE;
			$image_config['new_image'] = $thumbnail;
			$image_config['quality'] = '100%';
			$image_config['width'] = (int)$x_img;
			$image_config['height'] = (int)$y_img;
			$image_config['master_dim'] = (($img_width / $img_height) > ($x_img / $y_img) ? 'height' : 'width');
			$CI->image_lib->initialize($image_config);
			$CI->image_lib->resize();
			$CI->image_lib->clear();
			
			//Crop center
			list($new_width, $new_height) = getimagesize($gambar);
			$image_config = array();
			$image_config['image_library'] = 'gd2';
			$image_config['source_image'] = $gambar;
			$image_config['new_image'] = $thumbnail;
			$image_config['quality'] = '80%';
			$image_config['maintain_ratio'] = FALSE;
			$image_config['width'] = (int)$x_img;
			$image_config['height'] = (int)$y_img;
			$image_config['x_axis'] = round(($new_width - $x_img) / 2);
			$image_config['y_axis'] = round(($new_height - $y_img) / 2);
			$image_config['master_dim'] = 'auto';
			$CI->image_lib->initialize($image_config);
			$CI->image_lib->crop();			
			$CI->image_lib->clear();
			
			// Rotate image correctly!
			$exif = @exif_read_data($data);
			if(!empty($exif) && isset($exif['Orientation'])){
				$image_config = array();
				$image_config['image_library'] = 'gd2';
				$image_config['source_image'] = $gambar;
				$image_config['new_image'] = $thumbnail;
				switch ($exif['Orientation']) {
					case 1: // nothing
						break;
					case 2: // horizontal flip
						$image_config['rotation_angle'] = 'hor';
						break;
					case 3: // 180 rotate left
						$image_config['rotation_angle'] = '180';
						break;
					case 4: // vertical flip
						$image_config['rotation_angle'] = 'vrt';
						break;
					case 5: // vertical flip + 90 rotate right
						$image_config['rotation_angle'] = 'vrt';
						break;
					case 6: // 90 rotate right
						$image_config['rotation_angle'] = '270';
						break;
					case 7: // horizontal flip + 90 rotate right
						$image_config['rotation_angle'] = 'hor';
						break;
					case 8:    // 90 rotate left
						$image_config['rotation_angle'] = '90';
						break;
				}
				$CI->image_lib->initialize($image_config);
				$CI->image_lib->rotate();
				$CI->image_lib->clear();
			}
		}
		
		return s3content('resize/'.$size.'/'.$img);
	}
}

if ( ! function_exists('clean_html'))
{	
	function clean_html($string = null) {
		if(! $string){ return FALSE; }
		
		$string = preg_replace('/<[^<|>]+?>/', '', htmlspecialchars_decode($string));
		$string = str_replace('"', '', remove_invisible_characters(stripslashes(strip_tags(trim($string)))));
		
		return $string;
	}
}

if ( ! function_exists('msg'))
{
	function msg($key = null, $arg = '', $redirect = '', $info = ''){
		if(! $key){
			return FALSE;
		}
		
		// MOVE to DB
		$message = array(
			'ObjectNotFound' => 'Permintaan tidak ditemukan, silakan periksa kembali.',
			'ExpiredRequest' => 'Permintaan sudah kadaluarsa. Silakan ulangi kembali',
			'NotNull' => 'Harap diisi',
			'InvalidArgument' => 'Data yang Anda masukkan tidak valid. Hal ini dikarenakan data tidak sesuai dengan permintaan.',
			'InvalidMethod' => 'HTTP request yang Anda pilih salah',
			'InvalidState' => 'Kondisi yang dipilih salah.',
			'TypeMismatch' => 'Maaf, data tidak sesuai dengan tipe yang tersedia.',
			'MissingId' => 'ID tidak ditemukan, silakan periksa kembali.',
			'OverwriteException' => 'Data yang dikirim tidak valid, silakan periksa kembali.',
			'InvalidFileFormat' => 'Format yang dipilih salah.',
			'InvalidError' => ' Perintah yang diberikan tidak valid.',
			'ObjectCannotBeSaved' => 'Kesalahan saat menyimpan objek!',
			'InsufficientPermissions' => 'Permintaan tidak memiliki izin untuk mengakses data ini.',
			'SystemError' => 'Sistem mengalami gangguan, mohon tunggu beberapa saat.',
			'UniqueError' => 'Data sudah tersedia, silakan gunakan yang lainnya.',
			'ErrorAction' => 'Proses error!',
			'SuccessAction' => 'Proses berhasil!',
			'LoginError' => 'Login gagal, silahkan cek kembali akun Anda!',
			'registerError' => 'Registrasi gagal, email sudah terdaftar!',
			'LoginSuccess' => 'Selamat datang kembali',
			'LimitRequest' => 'Melebihi batas per hari. Silakan ulangi esok hari.',
		);
		
		if(translation($key) != ''){
			$string = translation($key);
		}else{
			$string = $key;
		}
		
		//Output
		return array(
			'message' => ($info ? $info : $string),
			'field' => $arg,
			'redirect' => $redirect,
		);
	}
}

if ( ! function_exists('translation'))
{
	function translation($id = null, $transform = null) {
		$CI =& get_instance();
		$current_lang = ($CI->isheaderallowed['language'] ? $CI->isheaderallowed['language'] : 'en');
		
		// DB
		$master =  $CI->db->select('lang_key, '.$current_lang.' AS title')->get('translation')->result();
		
		$master_key = array();
		if($master){
			foreach($master AS $red){
				$master_key[$red->lang_key] = $red->title;
			}
		}
		
		if($transform){
			$string = strtolower((isset($master_key[$id]) ? $master_key[$id] : ''));
			if($transform == 'ucwords'){
				return ucwords($string);
			}elseif($transform == 'ucfirst'){
				return ucfirst($string);
			}elseif($transform == 'uppercase'){
				return strtoupper($string);
			}else{
				return $string;
			}
		}else{
			return (isset($master_key[$id]) ? $master_key[$id] : '');
		}
	} 
}

if ( ! function_exists('translation_day'))
{	
	function translation_day($day = 0) {
		$CI =& get_instance();
		$lang = ($CI->isheaderallowed['language'] ? $CI->isheaderallowed['language'] : 'en');
		
		if($lang == 'en'){
			$list_days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		}else{
			$list_days = array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
		}
		
		return (isset($list_days[$day]) ? $list_days[$day] : FALSE);
	}
}

if ( ! function_exists('indonesia_date'))
{	
	function indonesia_date($timestamp = null, $format = 'Y-m-d H:i:s') {
		if(! $timestamp){ return now(); }
		
		//Month
		$timestamp = (int)$timestamp;
		$output = '';
		$list_month = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
		$list_days = array('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
		
		//Split format
		$split = str_split($format);
		foreach($split AS $red){
			if(strtolower($red) == 'y'){ //year
				$output .= date('Y', $timestamp);
			}elseif(strtolower($red) == 'm'){ //month
				$output .= $list_month[(int)date(strtolower($red), $timestamp)-1];
			}elseif(strtolower($red) == 'd'){ //days date
				$output .= (int)date($red, $timestamp);
			}elseif(strtolower($red) == 'h'){ //hour
				$output .= date($red, $timestamp);
			}elseif(strtolower($red) == 'i'){ //minute
				$output .= date($red, $timestamp);
			}elseif(strtolower($red) == 's'){ //second
				$output .= date($red, $timestamp);
			}elseif(strtolower($red) == 'n'){ //days string
				$output .= $list_days[(int)date(strtoupper($red), $timestamp)-1];
			}else{
				$output .= $red;
			}
		}
		
		return $output;
	}
}

if ( ! function_exists('url_1hour'))
{	
	function url_1hour($slug = null) {
		if(! $slug){ return FALSE; }
		
		$data = enc_dec('d', $slug);
		$row = explode('#', $data);
		if((strtotime(date('Y-m-d H:i:s')) - strtotime($row[1])) <= 3600){
			return TRUE;
		}else{
			return FALSE;
		}
	}
}

if ( ! function_exists('auto_url'))
{	
	function auto_url($slug = null) {
		if(! $slug){ return FALSE; }
		
		$data = enc_dec('e', $slug.'#'.date('Y-m-d H:i:s')); //$slug = id/unique
		return $data;
	}
}

if ( ! function_exists('slug'))
{
	function slug($title) {
		if(empty($title)){ return false;}
		return preg_replace('/-$/', '', preg_replace('/^-/', '', preg_replace('/\-{2,}/', '-', preg_replace('/([^a-z0-9]+)/', '-',strtolower($title)))));
	}
}

if ( ! function_exists('slug_uniqify'))
{
	function slug_uniqify($slug_candidate, $slug_possible_conflicts = array()) {
		$ci =& get_instance();
		$ci->load->helper('string');
		while (in_array($slug_candidate, $slug_possible_conflicts)) {
			$slug_candidate = increment_string($slug_candidate, '-');
		}
		return $slug_candidate;
	} 
}

if ( ! function_exists('__crypto_rand_secure'))
{
	function __crypto_rand_secure($min, $max)
	{
		$range = $max - $min;
		if ($range < 1) return $min;
		$log = ceil(log($range, 2));
		$bytes = (int) ($log / 8) + 1;
		$bits = (int) $log + 1;
		$filter = (int) (1 << $bits) - 1;
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter;
		} while ($rnd >= $range);
		return $min + $rnd;
	}
}

if ( ! function_exists('genToken'))
{
	function genToken($length = 6)
	{
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet) - 1;
		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[__crypto_rand_secure(0, $max)];
		}
		return $token;
	}
}

if ( ! function_exists('uniqueRandom'))
{
	function uniqueRandom($min, $max, $quantity = 10) {
		$numbers = range($min, $max);
		shuffle($numbers);
		return array_slice($numbers, 0, $quantity);
	}
}

if ( ! function_exists('indonesia_day'))
{	
	function indonesia_day() {
		return array('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
	}
}

if ( ! function_exists('save_image'))
{	
	function save_image($base64_string = null, $directory = null, $filename = null) {
		if(! $base64_string){ return FALSE; }
		if(! $directory){ return FALSE; }
		
		if(! $filename){
			$filename = sha1(auto_url(genToken(13))) . '.png';
		}else{
			$filename = strtolower($filename);
		}
		
		$data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_string));
		$success = file_put_contents(APPPATH.'../storage/'.$directory.'/' . $filename, $data);
		
		if($success){
			$string = $filename;
		}else{
			$string = FALSE;
		}
		
		return $string;
	}
}


if ( ! function_exists('digitToken'))
{	
	function digitToken() {
		$verify = uniqueRandom(1000, 9999);
		return $verify[1];
	}
}

/* End of file encdec_helper.php */
/* Location: ./helpers/encdec_helper.php */