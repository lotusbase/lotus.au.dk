<?php

namespace LotusBase\Component;

/* Component\Pagination */
class Paginate {

	private $pagination_content = array();

	// Set query strings
	public function set_query_string($query_string_array) {
		$this->pagination_content['query_string_array'] = $query_string_array;
	}

	// Set current page
	public function set_current_page($current_page) {
		$this->pagination_content['current_page'] = intval($current_page);
	}

	// Set last page
	public function set_last_page($last_page) {
		$this->pagination_content['last_page'] = intval($last_page);
	}

	// Set number of rows per page
	public function set_rows_per_page($rows_per_page) {
		$this->pagination_content['rows_per_page'] = intval($rows_per_page);
	}

	// Set version
	public function set_version($version) {
		$this->pagination_content['version'] = implode(',', $version);
	}

	// Encode URL
	private function to_url($str) {
		return urlencode($str);
	}

	// Return query string
	private function get_query_string($query_string_array) {
		$query_string = '';
		foreach($query_string_array as $query_key => $query_value) {
			if(is_array($query_value)) {
				$query_value = implode(',', $query_value);
			}
			$query_string .= '&'.$this->to_url($query_key).'='.$this->to_url($query_value);
		}
		return $query_string;
	}

	// Generate individual links
	private function get_link($q, $i, $n, $v, $c) {
		return '<a href="'.$_SERVER['PHP_SELF'].'?'.$q.'&p='.$i.'&n='.$n.'&version='.$v.'" class="'.($c === $i ? 'current ' : '').'button" title="Page '.$i.'">'.$i.'</a>';
	}

	// Generate pagination links
	public function get_pagination() {
		// Only generate links if there is more than one page
		if($this->pagination_content['last_page'] > 1) {

			// Internal variables
			$current_page	= $this->pagination_content['current_page'];
			$query			= $this->get_query_string($this->pagination_content['query_string_array']);
			$rows_per_page	= $this->pagination_content['rows_per_page'];
			$version		= $this->pagination_content['version'];

			// Previous and next pages
			$prev = $current_page - 1;
			$next = $current_page + 1;
			$last = $this->pagination_content['last_page'];

			// Output
			$nav = '<nav class="wrapper page-nav cols">';

			// If there are more than one page
			if($last > 1) {

				// If not on first page, allow users to navigate to previous pages
				if($current_page !== 1) {
					$nav .= '<a role="secondary" href="'.$_SERVER['PHP_SELF'].'?'.$query.'&p=1&n='.$rows_per_page.'&version='.$version.'" title="&laquo; First page" class="arrow button"><span class="icon-left-open-big icon--no-spacing"></span><span class="icon-left-open-big icon--no-spacing"></span></a><!--
						  --><a role="secondary" href="'.$_SERVER['PHP_SELF'].'?'.$query.'&p='.$prev.'&n='.$rows_per_page.'&version='.$version.'" title="&laquo; Previous page" class="arrow button"><span class="icon-left-open-big icon--no-spacing"></span></a>';
				}

				// If there are less than 10 pages, list all of them
				if($last < 10) {
					for($i=1; $i<=$last; $i++) {
						$nav .= $this->get_link($query, $i, $rows_per_page, $version, $current_page);
					}
				}
				// If there are more than 10 pages, list some of them
				else {
					if($current_page<=5) {
						for($i=1;$i<=9;$i++) {
							$nav .= $this->get_link($query, $i, $rows_per_page, $version, $current_page);
						}
						$nav .= '<span role="secondary" class="button">[&hellip;]</span>';
					} elseif($current_page>5 && $current_page<=$last-5) {
						$nav .= '<span role="secondary" class="button">[&hellip;]</span>';
						for($i=$current_page-4;$i<=$current_page+4; $i++) {
							$nav .= $this->get_link($query, $i, $rows_per_page, $version, $current_page);
						}
						$nav .= '<span role="secondary" class="button">[&hellip;]</span>';
					} else {
						$nav .= '<span role="secondary" class="button">[&hellip;]</span>';
						for($i=$last-8;$i<=$last;$i++) {
							$nav .= $this->get_link($query, $i, $rows_per_page, $version, $current_page);
						}
					}
				}

				// If not on last page, allow users to navigate to next pages
				if($current_page < $last) {
					$nav .= '<a role="secondary" href="'.$_SERVER['PHP_SELF'].'?'.$query.'&p='.$next.'&n='.$rows_per_page.'&version='.$version.'" title="Next Page &raquo;" class="arrow button"><span class="icon-right-open-big icon--no-spacing"></span></a><!--
						  --><a role="secondary" href="'.$_SERVER['PHP_SELF'].'?'.$query.'&p='.$last.'&n='.$rows_per_page.'&version='.$version.'" title="Last Page &raquo;" class="arrow button"><span class="icon-right-open-big icon--no-spacing"></span><span class="icon-right-open-big icon--no-spacing"></span></a>';
				}
			}

			// Output
			$nav .= '</nav>';

			// Echo output
			return $nav;
		}
	}
}
?>