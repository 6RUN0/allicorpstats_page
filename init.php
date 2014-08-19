<?php

/**
 * @author Andy Snowden
 * @copyright 2011
 * @version 1.0
 */

event::register('allianceDetail_context_assembling', 'allicorpstats_view::add');

class allicorpstats_view {

  function add($pAllianceDetail) {
    $pAllianceDetail->addBehind('menuSetup', 'allicorpstats_view::menuItems');
  }

  function menuItems($pAllianceDetail) {
    $uri = array(0 => array('a', 'corp_stats', TRUE));
    if($pAllianceDetail->all_external_id) {
      $uri[] = array('all_ext_id', $pAllianceDetail->all_external_id, TRUE);
    }
    elseif($pAllianceDetail->all_id) {
      $uri[] = array('all_id', $pAllianceDetail->all_id, TRUE);
    }
    $pAllianceDetail->addMenuItem('link', 'Alliance Corp Stats', edkURI::build($uri));
  }

}
