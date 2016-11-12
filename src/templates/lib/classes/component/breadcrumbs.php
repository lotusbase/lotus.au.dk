<?php

namespace LotusBase\Component;

/* Component\Breadcrumbs */
class Breadcrumbs {

	private $breadcrumb = array();

	// Construct
	public function __construct() {

		$crumbs = array_values(array_filter(explode("/",strtok($_SERVER['REQUEST_URI'],'?'))));
		$this->breadcrumb['crumbs_path'] = $crumbs;
		$this->breadcrumb['crumbs_titles'] = $crumbs;
	}

	// Set page title
	public function set_page_title($page_title) {
		if(!empty($page_title)) {
			if(is_string($page_title)) {
				array_pop($this->breadcrumb['crumbs_titles']);
				array_push($this->breadcrumb['crumbs_titles'], $page_title);
			} else if(is_array($page_title)) {
				$this->breadcrumb['crumbs_titles'] = $page_title;
			}
		}
	}

	// Set page titles
	public function set_page_titles($page_titles) {
		$this->set_page_title($page_titles);
	}

	// Set entire path
	public function set_crumbs($crumbs) {
		if(!empty($crumbs) && is_array($crumbs)) {
			foreach($crumbs as $title => $path) {
				$custom_breadcrumb_titles[] = $title;
				$custom_breadcrumb_paths[] = $path;
			}
			$this->breadcrumb['crumbs_path'] = $custom_breadcrumb_paths;
			$this->breadcrumb['crumbs_titles'] = $custom_breadcrumb_titles;
		}
	}

	// Get breadcrumbs
	public function get_breadcrumbs() {
		$out = '<section class="wrapper" id="breadcrumb"><nav><ul itemscope itemtype="http://schema.org/BreadcrumbList"><li><a href="'.WEB_ROOT.'/" title="Home"><em>Lotus</em> Base</a></li>';
		foreach($this->breadcrumb['crumbs_path'] as $key => $crumb){
			// Append
			$crumb_title = ucwords(str_replace(array(".php","_","-"),array(""," ", " "),$this->breadcrumb['crumbs_titles'][$key]));
			$out .= '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a href="'.WEB_ROOT.'/'.implode('/', array_slice($this->breadcrumb['crumbs_path'], 0, $key+1)).'" title="'.$crumb_title.'" itemprop="item"><span itemprop="name">'.$crumb_title.'</span></a><meta itemprop="position" content="'.($key).'" /></li>';
		}
		$out .= '</ul></nav></section>';
		return $out;
	}
}
?>