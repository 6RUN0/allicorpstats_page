<?php

/**
 * @author Andy Snowden
 * @copyright 2011
 * @version 1.0
 */
 
event::register("allianceDetail_context_assembling", "allicorpstats_view::add");

class allicorpstats_view {
	function add($page)
	{        
        $page->addMenuItem( "caption", "Mods:");
		$page->addMenuItem( "link","Alliance Corp Stats", "?a=corp_stats&amp;all_id=" . $_GET['all_ext_id']);
        
	}
}
?>