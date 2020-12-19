<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// The following class generates VALID RFC 4211 COMPLIANT Universally Unique IDentifiers (UUID) version 3, 4 and 5
// UUIDs generated below validates using OSSP UUID Tool
// How to Usage
// Named-based UUID.
// $v3uuid = $this->uuid->v3('1546058f-5a25-1234-85ae-e68f2a44bbaf', 'SomeRandomString');
// $v5uuid = $this->uuid->v5('1546058f-5a25-1234-85ae-e68f2a44bbaf', 'SomeRandomString');

// Pseudo-random UUID
// $v4uuid = $this->uuid->v4();

// Check valid UUID
// $isValid = $this->uuid->is_valid('1546058f-5a25-1234-85ae-e68f2a44bbaf');

class Uuid {

	public function __construct()
    {
		$this->CI =& get_instance();
    }
	
	public function v3($namespace, $name)
	{
		if(!$this->is_valid($namespace)) return false;
		
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);
		
		// Binary Value
		$nstr = '';
		
		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		}
		
		// Calculate hash value
		$hash = md5($nstr . $name);
		
		return sprintf('%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr($hash, 0, 8),
			
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 3
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			
			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}
	
	public function v4() 
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	public function v5($namespace, $name) 
	{
		if(!$this->is_valid($namespace)) return false;
		
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);
		
		// Binary Value
		$nstr = '';
		
		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		}
		
		// Calculate hash value
		$hash = sha1($nstr . $name);
		
		return sprintf('%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr($hash, 0, 8),
			
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 5
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			
			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}

	public function is_valid($uuid) {
		return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.'[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
	}
	
	public function token($lenght = 6) {
		if (function_exists("random_bytes")) {
			$bytes = random_bytes(ceil($lenght / 2));
		} elseif (function_exists("openssl_random_pseudo_bytes")) {
			$bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
		} else {
			throw new Exception("no cryptographically secure random function available");
		}
		
		return substr(bin2hex($bytes), 0, $lenght);
	}
}

/* End of file Uuid.php */
/* Location: ./application/libraries/Uuid.php */