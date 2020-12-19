<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_mod extends CI_Model {

	var $lang;
	
	function __construct(){
		parent::__construct();
		
		$this->lang = $this->isheaderallowed['language'];
	}
	
	//Detail
	function detail_ownerbar($cid = null, $related = FALSE)
	{
		if(! $cid){ return FALSE; }
		
		$data = $this->db
			->select('bar.*')
			->select('category.id_title AS category_title')
			->join('category', 'category.category_id = bar.category_id', 'inner')
			->limit(1)
			->get_where('bar', array('bar.owner_id' => $cid))
			->row();
			
		if($data){
			$data->cover = s3content('bar/'.$data->cover);
			$data->qrcode = s3content('qrcode/'.$data->qrcode.'.png');
			$data->slug = weburl('bar/'.$data->slug);
			$data->id_description = $this->emoji->decode($data->id_description);
			$data->en_description = $this->emoji->decode($data->en_description);
			
			if($data->id_prediction_open){
				$data->id_prediction_open = $this->emoji->decode($data->id_prediction_open);
			}
			
			if($data->en_prediction_open){
				$data->en_prediction_open = $this->emoji->decode($data->en_prediction_open);
			}
			
			if($related){
				$data->product = $this->result_bar_product(array('bar_id' => $data->bar_id, 'published' => 'all'));
				$data->suggest = $this->result_bar_suggest(array('bar_id' => $data->bar_id, 'published' => 'all'));
				$data->safety_protocol = $this->result_bar_safety(array('bar_id' => $data->bar_id, 'published' => 'all'));
			}
		}
		
		return $data;
	}
	
	// Auto complete - START
	function result_bar_product($filter = array())
	{
		//Query result
		$this->db->select('product.product_id AS id, product.title AS value');
		$this->db->join('product', 'product.product_id = bar_product.product_id', 'inner');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_product.bar_id', $filter['bar_id']);
		}
		
		if(isset($filter['published']) == FALSE){
			$this->db->where('product.published', 'publish');
		}
		
		$this->db->order_by('bar_product.node_id', 'DESC');
		
		return $this->db->get('bar_product')->result();
	}
	
	function result_bar_suggest($filter = array())
	{
		//Query result
		$this->db->select('suggest.suggest_id AS id, suggest.'.$this->lang.'_title AS value');
		$this->db->join('suggest', 'suggest.suggest_id = bar_suggest.suggest_id', 'inner');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_suggest.bar_id', $filter['bar_id']);
		}
		
		if(isset($filter['published']) == FALSE){
			$this->db->where('suggest.published', 'publish');
		}
		
		$this->db->order_by('bar_suggest.node_id', 'DESC');
		
		return $this->db->get('bar_suggest')->result();
	}
	
	function result_bar_safety($filter = array())
	{
		//Query result
		$this->db->select('safety_protocol.safety_id AS id, safety_protocol.'.$this->lang.'_title AS value');
		$this->db->join('safety_protocol', 'safety_protocol.safety_id = bar_safetyprotocol.safety_id', 'inner');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_safetyprotocol.bar_id', $filter['bar_id']);
		}
		
		if(isset($filter['published']) == FALSE){
			$this->db->where('safety_protocol.published', 'publish');
		}
		
		$this->db->order_by('bar_safetyprotocol.node_id', 'DESC');
		
		return $this->db->get('bar_safetyprotocol')->result();
	}
	// Auto complete - END
	
	function detail_page($cid = null, $type = 'slug')
	{
		if(! $cid){ return FALSE; }
		
		if($type == 'id'){
			$this->db->where('page_id', $cid);
		}else{
			$this->db->where('slug', $cid);
		}
		
		$data = $this->db
			->select('page_id,'.$this->lang.'_title AS title, '.$this->lang.'_description AS description, cover, cover_mobile, slug, meta_title, meta_description, meta_keyword')
			->limit(1)
			->get_where('page', array('published' => 'publish'))
			->row();
			
		if($data){
			$data->description = $this->emoji->decode($data->description);
			
			if($data->cover_mobile){
				$data->cover = s3content('page/'.$data->cover_mobile);
			}elseif($data->cover){
				$data->cover = s3content('page/'.$data->cover);
			}
			unset($data->{'cover_mobile'});
			
			$data->slug = weburl($data->slug);
		}
		
		return $data;
	}
	
	function detail_bar($cid = null, $type = 'slug')
	{
		if(! $cid){ return FALSE; }
		
		if($type == 'id'){
			$this->db->where('bar.bar_id', $cid);
		}else{
			$this->db->where('bar.slug', $cid);
		}
		
		$data = $this->db
			->select('bar.bar_id AS bar_id, bar.title AS title, bar.slug, bar.qrcode, bar.'.$this->lang.'_description AS description, bar.is_safe, bar.cover, bar.lat, bar.lng, bar.address, bar.count_view, bar.count_review, bar.rated, bar.is_highlight, bar.published, bar.created, bar.updated, bar.meta_title, bar.meta_description, bar.meta_keyword, bar.'.$this->lang.'_prediction_open AS prediction_open, bar.location, bar.facebook, bar.instagram, bar.website, bar.capacity, bar.is_promotion')
			->select('category.'.$this->lang.'_title AS category_title')
			->join('category', 'category.category_id = bar.category_id', 'inner')
			->limit(1)
			->get_where('bar', array('bar.published' => 'publish'))
			->row();
			
		if($data){
			$data->description = $this->emoji->decode($data->description);
			$data->cover = s3content('bar/'.$data->cover);
			$data->qrcode = s3content('qrcode/'.$data->qrcode.'.png');
			$data->slug = weburl('bar/'.$data->slug);
		}
		
		return $data;
	}
	
	function detail_safety($cid = null, $type = 'slug')
	{
		if(! $cid){ return FALSE; }
		
		if($type == 'id'){
			$this->db->where('safety_protocol.safety_id', $cid);
		}else{
			$this->db->where('safety_protocol.slug', $cid);
		}
		
		$data = $this->db
			->select('safety_protocol.safety_id AS safety_id, safety_protocol.'.$this->lang.'_title AS title, safety_protocol.slug, safety_protocol.'.$this->lang.'_description AS description, safety_protocol.cover, safety_protocol.video, safety_protocol.count_view, safety_protocol.count_review, safety_protocol.is_highlight, safety_protocol.published, safety_protocol.created, safety_protocol.updated, safety_protocol.meta_title, safety_protocol.meta_description, safety_protocol.meta_keyword')
			->select('member.fullname AS author')
			->join('member', 'member.member_id = safety_protocol.created_by', 'inner')
			->limit(1)
			->get_where('safety_protocol', array('safety_protocol.published' => 'publish'))
			->row();
			
		if($data){
			$data->description = $this->emoji->decode($data->description);
			$data->cover = s3content('safety/'.$data->cover);
			$data->slug = weburl('safety-protocol/'.$data->slug);
			$data->author = translation('by').' '.$data->author.' - '.indonesia_date(strtotime($data->created), 'd m Y');
		}
		
		return $data;
	}
	
	//Result
	function result_bar($limit = 0, $offset = 0, $filter = array())
	{
		// Sub Query, 1 miles = 3959 , 1 km = 6371
		$subqueryRadius = '';
		if(isset($filter['radius']) == TRUE && $filter['radius'] != ''){
			$lat = (isset($filter['radius']['lat']) ? $filter['radius']['lat'] : 0);
			$lng = (isset($filter['radius']['lng']) ? $filter['radius']['lng'] : 0);
			$range = (isset($filter['radius']['range']) ? $filter['radius']['range'] : 2);
			
			$subqueryRadius = $this->db
				->select('bar_id AS barid')
				->select('(6371 * acos(cos(radians('.$lat.')) * cos(radians(lat)) * cos(radians(lng) - radians('.$lng.')) + sin(radians('.$lat.')) * sin(radians(lat)))) AS distance')
				->having('distance <=',  $range)
				->where(array('published' => 'publish'))
				->order_by('distance', 'ASC')
				->get_compiled_select('bar');
		}
		
		// Opening by today / now
		$subqueryOpen = '';
		if(isset($filter['open']) == TRUE && $filter['open'] == 'true'){
			$today = date('w');
			$clock = date('H:00:00');
			
			$subqueryOpen = $this->db
				->select('bar_id AS bar_open')
				->where('days', $today)
				->group_start()
					->where('open <=', $clock)
					->where('close >', $clock)
				->group_end()
				->order_by('days,open', 'ASC')
				->get_compiled_select('bar_openinghour');
		}
		
		//product
		$bar_product = array();
		if(isset($filter['product']) == TRUE && $filter['product'] != NULL){
			$filterproduct = explode(',', $filter['product']);
			$product = $this->db
				->where_in('product_id', $filterproduct)
				->get('bar_product')
				->result();
			foreach($product AS $red){
				$bar_product[] = $red->bar_id;
			}
		}
		
		//suggest
		$bar_suggest = array();
		if(isset($filter['suggest']) == TRUE && $filter['suggest'] != NULL){
			$filtersuggest = explode(',', $filter['suggest']);
			$suggest = $this->db
				->where_in('suggest_id', $filtersuggest)
				->get('bar_suggest')
				->result();
			foreach($suggest AS $red){
				$bar_suggest[] = $red->bar_id;
			}
		}
		
		//Query result
		$this->db->select('bar.bar_id AS bar_id, bar.title AS title, bar.slug, bar.qrcode, bar.'.$this->lang.'_description AS description, bar.is_safe, bar.cover, bar.lat, bar.lng, bar.address, bar.count_view, bar.count_review, bar.rated, bar.is_highlight, bar.published, bar.created, bar.updated, bar.meta_title, bar.meta_description, bar.meta_keyword, bar.capacity, bar.is_promotion');
		$this->db->select('category.'.$this->lang.'_title AS category_title');
		$this->db->join('category', 'category.category_id = bar.category_id', 'inner');
		
		if($subqueryRadius){
			$this->db->select('d.distance');
			$this->db->join("($subqueryRadius) AS d", 'd.barid = bar.bar_id', 'inner');
		}
		
		if($subqueryOpen){
			$this->db->join("($subqueryOpen) AS o", 'o.bar_open = bar.bar_id', 'inner');
		}
		
		if($bar_product){
			$this->db->where_in('bar.bar_id', $bar_product);
		}
		
		if($bar_suggest){
			$this->db->where_in('bar.bar_id', $bar_suggest);
		}
		
		if(isset($filter['owner_id']) == TRUE && $filter['owner_id'] != ''){
			$this->db->where('bar.owner_id', $filter['owner_id']);
		}
		
		if(isset($filter['promotion']) == TRUE && $filter['promotion'] != ''){
			$this->db->where('bar.is_promotion', $filter['promotion']);
		}
		
		if(isset($filter['keyword']) == TRUE && $filter['keyword'] != ''){
			$this->db->group_start();
			
			$skey = array_map('trim', explode(' ', clean_html($filter['keyword'])));
			$this->db->like('bar.title', $filter['keyword'], 'both');
			foreach($skey AS $red){
				$this->db->or_like('bar.title', $red, 'both');
			}
			
			$this->db->group_end();
		}
		
		if(isset($filter['category']) == TRUE && $filter['category'] != ''){
			$this->db->where('bar.category_id', $filter['category']);
		}
		
		if(isset($filter['rate']) == TRUE && $filter['rate'] != ''){
			$this->db->where('bar.rated >=', $filter['rate']);
		}
		
		if(isset($filter['location']) == TRUE && $filter['location'] != ''){
			$this->db->where('bar.location', $filter['location']);
		}
		
		if(isset($filter['not_in_id']) == TRUE && $filter['not_in_id'] != NULL && is_array($filter['not_in_id']) == TRUE){
			$this->db->where_not_in('bar.bar_id', $filter['not_in_id']);
		}
		
		if($subqueryRadius){
			$this->db->order_by('d.distance', 'ASC');
		}else{
			if(isset($filter['sort']) == TRUE && $filter['sort'] != ''){
				switch($filter['sort']){
					case 'az':
						$this->db->order_by('bar.title', 'ASC');
						break;
					case 'za':
						$this->db->order_by('bar.title', 'DESC');
						break;
					case 'asc':
						$this->db->order_by('bar.created', 'ASC');
						break;
					case 'popular':
						if(isset($filter['related'])){
							$this->db->where('bar.updated >=', date('Y-m-d H:i:s', strtotime('-1month')));
						}
						
						$this->db->order_by('bar.count_view', 'DESC');
						break;
					case 'highlight':
						$this->db->where('bar.is_highlight IS NOT NULL', NULL, FALSE);
						$this->db->order_by('bar.is_highlight', 'DESC');
						break;
					case 'random':
						$this->db->order_by('RAND()');
						break;
					default:
						$this->db->order_by('bar.created', 'DESC');
				}
			}else{
				$this->db->order_by('bar.created', 'DESC');
			}
		}
		
		//Global condition
		$this->db->where('bar.published', 'publish');
		
		if($limit){
			$data = $this->db->limit($limit, $offset)->get('bar')->result();
			
			if($data){
				foreach($data AS $red){
					$red->description = character_limiter(clean_html($red->description), 160);
					$red->cover = s3content('bar/'.$red->cover);
					$red->qrcode = s3content('qrcode/'.$red->qrcode.'.png');
					$red->slug = weburl('bar/'.$red->slug);
				}
			}
			
			return $data;
		}else{
			return $this->db->get('bar')->num_rows();
		}
	}
	
	function result_safety($limit = 0, $offset = 0, $filter = array())
	{
		//Query result
		$this->db->select('safety_protocol.safety_id AS safety_id, safety_protocol.'.$this->lang.'_title AS title, safety_protocol.slug, safety_protocol.'.$this->lang.'_description AS description, safety_protocol.cover, safety_protocol.video, safety_protocol.count_view, safety_protocol.count_review, safety_protocol.is_highlight, safety_protocol.published, safety_protocol.created, safety_protocol.updated, safety_protocol.meta_title, safety_protocol.meta_description, safety_protocol.meta_keyword');
		$this->db->select('member.fullname as author');
		$this->db->join('member', 'member.member_id = safety_protocol.created_by', 'inner');
		
		if(isset($filter['keyword']) == TRUE && $filter['keyword'] != ''){
			$this->db->group_start();
			
			$skey = array_map('trim', explode(' ', clean_html($filter['keyword'])));
			$this->db->like('safety_protocol.title', $filter['keyword'], 'both');
			foreach($skey AS $red){
				$this->db->or_like('safety_protocol.title', $red, 'both');
			}
			
			$this->db->group_end();
		}
		
		if(isset($filter['not_in_id']) == TRUE && $filter['not_in_id'] != NULL && is_array($filter['not_in_id']) == TRUE){
			$this->db->where_not_in('safety_protocol.safety_id', $filter['not_in_id']);
		}
		
		if(isset($filter['sort']) == TRUE && $filter['sort'] != ''){
			switch($filter['sort']){
				case 'asc':
					$this->db->order_by('safety_protocol.created', 'ASC');
					break;
				case 'popular':
					if(isset($filter['related'])){
						$this->db->where('safety_protocol.updated >=', date('Y-m-d H:i:s', strtotime('-1month')));
					}
					
					$this->db->order_by('safety_protocol.count_view', 'DESC');
					break;
				case 'highlight':
					$this->db->where('safety_protocol.is_highlight IS NOT NULL', NULL, FALSE);
					$this->db->order_by('safety_protocol.is_highlight', 'DESC');
					break;
				case 'random':
					$this->db->order_by('RAND()');
					break;
				default:
					$this->db->order_by('safety_protocol.created', 'DESC');
			}
		}else{
			$this->db->order_by('safety_protocol.created', 'DESC');
		}
		
		//Global condition
		$this->db->where('safety_protocol.published', 'publish');
		
		if($limit){
			$data = $this->db->limit($limit, $offset)->get('safety_protocol')->result();
			
			if($data){
				foreach($data AS $red){
					$red->description = character_limiter(clean_html($red->description), 160);
					$red->cover = s3content('safety/'.$red->cover);
					$red->slug = weburl('safety-protocol/'.$red->slug);
					$red->author = translation('by').' '.$red->author.' - '.indonesia_date(strtotime($red->created), 'd m Y');
				}
			}
			
			return $data;
		}else{
			return $this->db->get('safety_protocol')->num_rows();
		}
	}
	
	function result_bar_safetyprotocol($filter = array())
	{
		//Query result
		$this->db->select('safety_protocol.safety_id AS safety_id, safety_protocol.'.$this->lang.'_title AS title, safety_protocol.slug, safety_protocol.'.$this->lang.'_description AS description, safety_protocol.cover, safety_protocol.count_view, safety_protocol.count_review, safety_protocol.is_highlight, safety_protocol.published, safety_protocol.created, safety_protocol.updated, safety_protocol.meta_title, safety_protocol.meta_description, safety_protocol.meta_keyword');
		$this->db->join('safety_protocol', 'safety_protocol.safety_id = bar_safetyprotocol.safety_id', 'inner');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_safetyprotocol.bar_id', $filter['bar_id']);
		}
		
		$this->db->order_by('safety_protocol.created', 'DESC');
		
		//Global condition
		$this->db->where('safety_protocol.published', 'publish');
		
		$data = $this->db->get('bar_safetyprotocol')->result();
		
		if($data){
			foreach($data AS $red){
				$red->description = character_limiter(clean_html($red->description), 160);
				$red->cover = s3content('safety/'.$red->cover);
				$red->slug = weburl('safety-protocol/'.$red->slug);
			}
		}
		
		return $data;
	}
	
	function result_bar_photo($filter = array())
	{
		//Query result
		$this->db->select('node_id, cover, published, created, updated');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_id', $filter['bar_id']);
		}
		
		if(isset($filter['published']) == FALSE){
			$this->db->where('published', 'publish');
		}
		
		$this->db->order_by('created', 'DESC');
		
		$data = $this->db->get('bar_photo')->result();
		
		if($data){
			foreach($data AS $red){
				$red->cover = s3content('bar/'.$red->cover);
			}
		}
		
		return $data;
	}
	
	function result_bar_openinghour($filter = array())
	{
		//Query result
		$this->db->select('node_id, days, open, close');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_id', $filter['bar_id']);
		}
		
		$this->db->order_by('days, open', 'ASC');
		
		$data = $this->db->get('bar_openinghour')->result();
		
		if($data){
			foreach($data AS $red){
				$red->days = translation_day($red->days);
				$red->open = date('H:i', strtotime($red->open));
				$red->close = date('H:i', strtotime($red->close));
			}
		}
		
		return $data;
	}
	
	function result_safety_review($limit = 0, $offset = 0, $filter = array())
	{
		//Query result
		$this->db->select('safety_review.node_id, safety_review.comment, safety_review.published, safety_review.created, safety_review.updated');
		$this->db->select('safety_protocol.safety_id AS safety_id, safety_protocol.'.$this->lang.'_title AS title, safety_protocol.slug');
		$this->db->select('member.fullname, member.avatar, member.status, member.level');
		$this->db->join('safety_protocol', 'safety_protocol.safety_id = safety_review.safety_id', 'inner');
		$this->db->join('member', 'member.member_id = safety_review.member_id', 'inner');
		
		if(isset($filter['safety_id']) == TRUE && $filter['safety_id'] != ''){
			$this->db->where('safety_review.safety_id', $filter['safety_id']);
		}
		
		if(isset($filter['keyword']) == TRUE && $filter['keyword'] != ''){
			$this->db->group_start();
			
			$skey = array_map('trim', explode(' ', clean_html($filter['keyword'])));
			$this->db->like('safety_review.comment', $filter['keyword'], 'both');
			foreach($skey AS $red){
				$this->db->or_like('safety_review.comment', $red, 'both');
			}
			
			$this->db->group_end();
		}
		
		if(isset($filter['sort']) == TRUE && $filter['sort'] != ''){
			switch($filter['sort']){
				case 'asc':
					$this->db->order_by('safety_review.created', 'ASC');
					break;
				case 'random':
					$this->db->order_by('RAND()');
					break;
				default:
					$this->db->order_by('safety_review.created', 'DESC');
			}
		}else{
			$this->db->order_by('safety_review.created', 'DESC');
		}
		
		//Global condition
		$this->db->where('safety_review.published', 'publish');
		
		if($limit){
			$data = $this->db->limit($limit, $offset)->get('safety_review')->result();
			
			if($data){
				foreach($data AS $red){
					$red->comment = $this->emoji->decode($red->comment);
					$red->slug = weburl('safety-protocol/'.$red->slug);
					$red->avatar = foto('avatar/'.$red->avatar, 360, 360);
				}
			}
			
			return $data;
		}else{
			return $this->db->get('safety_review')->num_rows();
		}
	}
	
	function result_bar_review($limit = 0, $offset = 0, $filter = array())
	{
		//Query result
		$this->db->select('bar_review.review_id, bar_review.comment, bar_review.rate, bar_review.helpful, bar_review.unhelpful, bar_review.count_share, bar_review.published, bar_review.created, bar_review.updated');
		$this->db->select('bar.bar_id AS bar_id, bar.title, bar.slug');
		$this->db->select('member.fullname, member.avatar, member.status, member.level');
		$this->db->join('bar', 'bar.bar_id = bar_review.bar_id', 'inner');
		$this->db->join('member', 'member.member_id = bar_review.member_id', 'inner');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('bar_review.bar_id', $filter['bar_id']);
		}
		
		if(isset($filter['owner_id']) == TRUE && $filter['owner_id'] != ''){
			$this->db->where('bar.owner_id', $filter['owner_id']);
		}
		
		if(isset($filter['keyword']) == TRUE && $filter['keyword'] != ''){
			$this->db->group_start();
			
			$skey = array_map('trim', explode(' ', clean_html($filter['keyword'])));
			$this->db->like('bar_review.comment', $filter['keyword'], 'both');
			foreach($skey AS $red){
				$this->db->or_like('bar_review.comment', $red, 'both');
			}
			
			$this->db->group_end();
		}
		
		if(isset($filter['sort']) == TRUE && $filter['sort'] != ''){
			switch($filter['sort']){
				case 'asc':
					$this->db->order_by('bar_review.created', 'ASC');
					break;
				case 'random':
					$this->db->order_by('RAND()');
					break;
				default:
					$this->db->order_by('bar_review.created', 'DESC');
			}
		}else{
			$this->db->order_by('bar_review.created', 'DESC');
		}
		
		//Global condition
		$this->db->where('bar_review.published', 'publish');
		
		if($limit){
			$data = $this->db->limit($limit, $offset)->get('bar_review')->result();
			
			if($data){
				foreach($data AS $red){
					$red->comment = $this->emoji->decode($red->comment);
					$red->slug = weburl('bar/'.$red->slug);
					$red->avatar = foto('avatar/'.$red->avatar, 360, 360);
				}
			}
			
			return $data;
		}else{
			return $this->db->get('bar_review')->num_rows();
		}
	}
	
	function result_promotion($limit = 0, $offset = 0, $filter = array())
	{
		$this->db->select('url, '.$this->lang.'_title AS title');
		
		if(isset($filter['sort']) == TRUE && $filter['sort'] != ''){
			switch($filter['sort']){
				case 'asc':
					$this->db->order_by('created', 'ASC');
					break;
				case 'random':
					$this->db->order_by('RAND()');
					break;
				default:
					$this->db->order_by('created', 'DESC');
			}
		}else{
			$this->db->order_by('created', 'DESC');
		}
		
		//Global condition
		$this->db->where('duedate >=', date('Y-m-d'));
		
		if($limit){
			return $this->db->limit($limit, $offset)->get('promotion')->result();
		}else{
			return $this->db->get('promotion')->num_rows();
		}
	}
	
	function result_suggest()
	{
		$this->db->select('suggest_id, '.$this->lang.'_title AS title');
		$this->db->where('published', 'publish');
		$this->db->order_by($this->lang.'_title', 'ASC');
		
		return $this->db->get('suggest')->result();
	}
	
	function result_category()
	{
		$this->db->select('category_id, '.$this->lang.'_title AS title');
		$this->db->where('published', 'publish');
		$this->db->order_by($this->lang.'_title', 'ASC');
		
		return $this->db->get('category')->result();
	}
	
	function result_product()
	{
		$this->db->select('product_id, title');
		$this->db->where('published', 'publish');
		$this->db->order_by('title', 'ASC');
		
		return $this->db->get('product')->result();
	}
	
	function result_location()
	{
		$this->db->select('location');
		$this->db->where('location IS NOT NULL', NULL, FALSE);
		$this->db->group_by('location');
		$this->db->order_by('location', 'ASC');
		
		return $this->db->get_where('bar', array('location !=' => ''))->result();
	}
	
	function result_contact_us($limit = 0, $offset = 0, $filter = array())
	{
		//Query result
		$this->db->select('contact_us.*');
		$this->db->select('contact_topic.'.$this->lang.'_title AS topic');
		$this->db->join('contact_topic', 'contact_topic.topic_id = contact_us.topic_id', 'inner');
		
		if(isset($filter['bar_id']) == TRUE && $filter['bar_id'] != ''){
			$this->db->where('contact_us.bar_id', $filter['bar_id']);
		}
		
		$this->db->order_by('contact_us.created', 'DESC');
		
		if($limit){
			$data = $this->db->limit($limit, $offset)->get('contact_us')->result();
			
			if($data){
				foreach($data AS $red){
					$red->question = $this->emoji->decode($red->question);
				}
			}
			
			return $data;
		}else{
			return $this->db->get('contact_us')->num_rows();
		}
	}
	
	function result_page()
	{
		$data = $this->db
			->select('page_id,'.$this->lang.'_title AS title, '.$this->lang.'_description AS description, cover, cover_mobile, slug, meta_title, meta_description, meta_keyword')
			->get_where('page', array('published' => 'publish'))
			->result();
			
		if($data){
			foreach($data AS $red){
				$red->description = $this->emoji->decode($red->description);
				
				if($red->cover_mobile){
					$red->cover = s3content('page/'.$red->cover_mobile);
				}elseif($red->cover){
					$red->cover = s3content('page/'.$red->cover);
				}
				unset($red->{'cover_mobile'});
				
				$red->slug = weburl($red->slug);
			}
		}
		
		return $data;
	}
	
	function result_hero($limit = 0, $filter = array())
	{
		//Query result
		$this->db->order_by('created', 'DESC');
		
		if($limit){
			$this->db->limit($limit);
		}
		
		//Global condition
		$this->db->where('published', 'publish');
		
		return $this->db->get('hero')->result();
	}
}