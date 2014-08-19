<?php

require_once ('mods/allicorpstats_page/class.allicorpstats.php');

class pCorpStats extends pageAssembly {

  private $day;
  private $week;
  private $month;
  private $year;

  private $daterange;

  private $order;

  private $all_id;
  private $all_external_id;

  private $menuOptions = array();

  private $url;
  private $length_uri;

  public $page;

  function __construct() {
    parent::__construct();
    $this->queue('start');
    $this->queue('corpStatsTable');
  }

  function start() {

    $this->page = new Page();

    $this->all_id = (int) edkURI::getArg('all_id');
    $this->all_external_id = (int) edkURI::getArg('all_ext_id');
    $position = 1;
    if (!$this->all_id && !$this->all_external_id) {
      $id = edkURI::getArg('id', $position);
      if(is_numeric($id)) {
        $id = (int) $id;
        $position++;
        if ($this->is_external_id($id)) {
          $this->all_external_id = $id;
        }
        else {
          $this->all_id = $id;
        }
      }
    }

    $daterange = edkURI::getArg('daterange', $position);
    switch ($daterange) {
      case 'weekly':
        $this->daterange = $daterange;
        $this->week = $this->extractDate('w', $position + 1, 'W');
        $this->year = $this->extractDate('y', $position + 2, 'Y');
        break;
      case 'monthly':
        $this->daterange = $daterange;
        $this->month = $this->extractDate('m', $position + 1, 'm');
        $this->year = $this->extractDate('y', $position + 2, 'Y');
        break;
      case 'yearly':
        $this->daterange = $daterange;
        $this->year = $this->extractDate('y', $position + 1, 'Y');
        break;
      case 'alltime':
        $this->daterange = $daterange;
        break;
      default:
        $this->month = kbdate('m');
        $this->week = kbdate('W');
        $this->year = kbdate('Y');
        $this->daterange = config::get('allicorpstatspage_datefilter');
    }

    $this->uri = edkURI::parseURI();
    $this->length_uri = count($this->uri);

    $order = edkURI::getArg('order');
    if($this->orderValid($order)) {
      $this->order = $order;
    }

    //var_dump($this->order);
    //var_dump($this->all_id);
    //var_dump($this->all_external_id);
    //var_dump($this->daterange);
    //var_dump($this->week);
    //var_dump($this->year);
    //var_dump($this->month);

  }

  private function is_external_id($id) {
    // And now a bit of magic to test if this is an external ID
    if (($id > 500000 && $id < 500021) || $id > 1000000) {
      return TRUE;
    }
    return FALSE;
  }

  private function extractDate($name, $position, $format) {

    $result = edkURI::getArg($name, $position);
    if(!is_numeric($result)) {
      $result = kbdate($format);
    }
    return $result;

  }

  private function orderValid($order = NULL) {
    $valid_args = array(
      'nameasc',
      'tickerasc',
      'ceodesc',
      'membersdesc',
      'memberactsdesc',
      'memberactprozsdesc',
      'killsdesc',
      'killiskdesc',
      'lossesdesc',
      'lossiskdesc',
      'effdesc',
      'killrq',
    );
    if (isset($order) && in_array($order, $valid_args)) {
      return TRUE;
    }
    return FALSE;
  }

  function corpStatsTable() {

    global $smarty;

    if($this->all_id) {
      $id = $this->all_id;
    }
    elseif($this->all_external_id) {
      $id = $this->all_external_id;
    }
    else {
      $id = config::get('cfg_allianceid');
      $id = $id[0];
    }

    $corpStats = new AlliCorpStats($id);

    $corpStats->setOrder($this->order);
    $corpStats->setWeek($this->week);
    $corpStats->setMonth($this->month);
    $corpStats->setYear($this->year);

    switch($this->daterange) {
      case 'weekly':
       $smarty->assign('datefilter', "Week {$this->week}");
        break;
      case 'monthly':
        $date = date_create($this->year . '-' . $this->month);
        $smarty->assign('datefilter', date_format($date, 'F, Y'));
        break;
      case 'yearly':
        $smarty->assign('datefilter', "{$this->year}");
        break;
      case 'alltime':
        $corpStats->setStartDate('2003-01-01 00:00:00');
        $smarty->assign('datefilter', "All-Time");
        break;
    }

    return $corpStats->generate();

  }

	function context() {
		parent::__construct();
		$this->queue('menuSetup');
		$this->queue('menu');
	}

  function menuSetup() {

    $prefix = array();
    $suffix = array();

    $this->addMenuItem('caption', 'Period');
    $prefix[] = array('a', 'corp_stats', TRUE);
    if($this->all_id) {
      $prefix[] = array('all_id', $this->all_id, TRUE);
    }
    elseif($this->all_external_id) {
      $prefix[] = array('all_ext_id', $this->all_external_id, TRUE);
    }
    $all_time_uri = edkURI::build(array_merge($prefix, array('daterange' => array('daterange', 'alltime', TRUE)), $suffix));
    $this->addMenuItem('link', 'All time', $all_time_uri);
    $weekly_uri = edkURI::build(array_merge($prefix,array('' => array('daterange', 'weekly', TRUE)), $suffix));
    $this->addMenuItem('link', 'Weekly', $weekly_uri);
    $monthly_uri = edkURI::build(array_merge($prefix, array('' => array('daterange', 'monthly', TRUE)), $suffix));
    $this->addMenuItem('link', 'Monthly', $monthly_uri);
    $yearly_uri = edkURI::build(array_merge($prefix, array('' => array('daterange', 'yearly', TRUE)), $suffix));
    $this->addMenuItem('link', 'Yearly', $yearly_uri);

    $next_title = '';
    $prev_title = '';
    $next_uri = '';
    $prev_uri = '';
    $prefix[] = array('daterange', $this->daterange, TRUE);
    //var_dump($prefix);
    switch($this->daterange) {
      case 'weekly':
        $next_week = ($this->week == 53) ? 1 : $this->week + 1;
        $next_year = ($this->week == 53) ? $this->year + 1 : $this->year;
        $prev_week = ($this->week == 1) ? 53 : $this->week - 1;
        $prev_year = ($this->week == 1) ? $this->year - 1 : $this->year;
        $prev_title = 'Previous Week';
        $prev_uri = edkURI::build(array_merge($prefix, array('w' => array('w', $prev_week, TRUE), 'y' => array('y', $prev_year, TRUE)), $suffix));
        if($next_year < kbdate('Y') || ($next_year == kbdate('Y') && $next_week <= kbdate('W'))) {
          $next_title = 'Previous Week';
          $next_uri = edkURI::build(array_merge($prefix, array('w' => array('w', $next_week, TRUE), 'y' => array('y', $next_year, TRUE)), $suffix));
        }
        break;
      case 'monthly':
        $next_month = ($this->month == 12) ? 1 : $this->month + 1;
        $next_year = ($this->month == 12) ? $this->year + 1 : $this->year;
        $prev_month = ($this->month == 1) ? 12 : $this->month - 1;
        $prev_year = ($this->month == 1) ? $this->year - 1 : $this->year;
        $prev_title = 'Previous Month';
        $prev_uri = edkURI::build(array_merge($prefix, array('m' => array('m', $prev_month, TRUE), 'y' => array('y', $prev_year, TRUE)), $suffix));
        if ($next_year < kbdate('Y') || ($next_year == kbdate('Y') && $next_month <= kbdate('m'))) {
          $next_title = 'Next Month';
          $next_uri = edkURI::build(array_merge($prefix, array('m' => array('m', $next_month, TRUE), 'y' => array('y', $next_year, TRUE)), $suffix));
        }
        break;
      case 'yearly':
        $next_year = $this->year + 1;
        $prev_year = $this->year - 1;
        $prev_title = 'Previous Year';
        $prev_uri = edkURI::build(array_merge($prefix, array('y' => array('y', $prev_year, TRUE)), $suffix));
        if($next_year <= kbdate('Y')) {
          $next_title = 'Next Year';
          $next_uri = edkURI::build(array_merge($prefix, array('y' => array('y', $next_year, TRUE)), $suffix));
        }
        break;
    }
    if($next_uri != '' || $prev_uri != '') {
      $this->addMenuItem('caption', 'Date Navigation');
      if($next_uri != '') {
        $this->addMenuItem('link', $next_title, $next_uri);
      }
      if($prev_uri != '') {
        $this->addMenuItem('link', $prev_title, $prev_uri);
      }
    }


  }

	function addMenuItem($type, $name, $url = '') {
		$this->menuOptions[] = array($type, $name, $url);
  }

	function menu() {
		$menubox = new box('Corp Stats');
		$menubox->setIcon('menu-item.gif');
		foreach ($this->menuOptions as $options) {
			if (isset($options[2])) {
				$menubox->addOption($options[0], $options[1], $options[2]);
      }
      else {
				$menubox->addOption($options[0], $options[1]);
			}
		}
		return $menubox->generate();
	}

  function getDay() {
    return $this->day;
  }

  function getWeek() {
    return $this->week;
  }

  function getMonth() {
    return $this->month;
  }

  function getYear() {
    return $this->year;
  }

  function getDateRange() {
    return $this->daterange;
  }

}

$pageAssembly = new pCorpStats();
event::call('corpstat_assembling', $pageAssembly);
$html = $pageAssembly->assemble();
$pageAssembly->page->setContent($html);

$pageAssembly->context(); //This resets the queue and queues context items.
event::call('corpstat_context_assembling', $pageAssembly);
$contextHTML = $pageAssembly->assemble();
$pageAssembly->page->addContext($contextHTML);

$pageAssembly->page->generate();
