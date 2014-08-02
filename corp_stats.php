<?php

require_once ('common/includes/class.killsummarytablepublic.php');
require_once ('common/includes/class.killlist.php');
require_once ('common/includes/class.killlisttable.php');
require_once ('common/includes/class.contract.php');
require_once ('common/includes/class.toplist.php');
require_once ('common/includes/class.bargraph.php');
require_once ('mods/allicorpstats_page/class.allicorpstats.php');

// set some dates incase some wants to view stats for any week/month/year
$week = $_GET['w'];
$year = $_GET['y'];
$month = $_GET['m'];

if ($week == '')
    $week = kbdate('W');

if ($year == '')
    $year = kbdate('Y');

if ($month == '')
    $month = kbdate('m');

// start the new page and give it a title
$page = new Page('Alliance Corp Statistics');

if (isset($_GET['all_id']) and is_numeric($_GET['all_id'])){
    //trust user input, lol no
    $id = addslashes($_GET['all_id']);
} else {
    //else we grab the base internally set ID
    $id = config::get('cfg_allianceid');
    $id = $id[0];
}

$corpStats = new AlliCorpStats($id);

$daterange_year = $daterange_month = $daterange_week = false;

// start a switch to allow for viewing of other stats such as weekly, monthly etc
switch ($_GET['daterange']) {
    case 'weekly':

        $corpStats->setWeek($week);
        $corpStats->setYear($year);
        $smarty->assign('datefilter', "Week {$week}");

        $daterange_week = true;

        break;


    case 'monthly':

        $corpStats->setMonth($month);
        $corpStats->setYear($year);
        $timestamp = mktime(0, 0, 0, $month, 1, 2005);
        $smarty->assign('datefilter', date("F", $timestamp));

        $daterange_month = true;

        break;


    case 'yearly':

        $corpStats->setYear($year);
        $smarty->assign('datefilter', "{$year}");

        $daterange_year = true;

        break;


    case 'alltime':

        $corpStats->setStartDate('2003-01-01 00:00:00');
        $smarty->assign('datefilter', "All-Time");

        break;


    default:

        // get the date range based on what the admin selected in admin panel
        if (config::get('allicorpstatspage_datefilter') == 'weekly') {
            $corpStats->setWeek($week);
            $corpStats->setYear($year);
            $smarty->assign('datefilter', "Week {$week}");
            $daterange_week = true;
        } elseif (config::get('allicorpstatspage_datefilter') == 'monthly') {
            $corpStats->setMonth($month);
            $corpStats->setYear($year);
            $timestamp = mktime(0, 0, 0, $month, 1, 2005);
            $smarty->assign('datefilter', date("F", $timestamp));
            $daterange_month = true;
        } elseif (config::get('allicorpstatspage_datefilter') == 'yearly') {
            $corpStats->setYear($year);
            $smarty->assign('datefilter', "{$year}");
            $daterange_year = true;
        } else {
            $corpStats->setStartDate('2003-01-01 00:00:00');
            $smarty->assign('datefilter', "All-Time");
        }

        break;

}

$url_ext = '';

if (@$_GET['no_n00bs'] == 'true') {
    $url_ext = '&no_n00bs=true';
}

$killrq = $daterange_month;
$smarty->assign('killrq', $killrq);

$smarty->assign('no_n00bs', (@$_GET['no_n00bs'] == 'true'));

// append the content onto the end of $html
$html .= $corpStats->generate();

// make $html the content to display on the page
$page->setContent($html);

// create a menu box to display alltime/weekly/monthly/yearly links
$menubox = new box('Menu');
$menubox->setIcon('menu-item.gif');

$tblx = (!empty($_GET['w']) ? '&w=' . (int)$_GET['w'] : '') . (!empty($_GET['m']) ?
    '&m=' . (int)$_GET['m'] : '') . (!empty($_GET['y']) ? '&y=' . (int)$_GET['y'] :
    '') . (!empty($_GET['daterange']) ? '&daterange=' . (string )$_GET['daterange'] :
    '') . (!empty($_GET['order']) ? '&order=' . (string )$_GET['order'] : '');

if (@$_GET['no_n00bs'] != 'true') {
    $menubox->addOption('link', '<b>Remove</b> Noobship, Shuttle, Capsule',
        '?a=corp_stats&no_n00bs=true' . $tblx);
} else {
    $menubox->addOption('link', '<b>Show</b> Noobship, Shuttle, Capsule',
        '?a=corp_stats' . $tblx);
}

$menubox->addOption('caption', 'Corp Stats');
$menubox->addOption('link', 'All-Time', '?a=corp_stats&amp;daterange=alltime' .
    $url_ext);
$menubox->addOption('link', 'Weekly', '?a=corp_stats&amp;daterange=weekly' . $url_ext);
$menubox->addOption('link', 'Monthly', '?a=corp_stats&amp;daterange=monthly' . $url_ext);
$menubox->addOption('link', 'Yearly', '?a=corp_stats&amp;daterange=yearly' . $url_ext);

if ($daterange_year || $daterange_month || $daterange_week) {

    $menubox->addOption('caption', 'Date Navigation');

    //
    if ($daterange_week) { // week(s) year = 53
        $next_week = $week == 53 ? 1 : $week + 1;
        $next_year = $week == 53 ? $year + 1 : $year;

        $prev_week = $week == 1 ? 53 : $week - 1;
        $prev_year = $week == 1 ? $year - 1 : $year;

        $menubox->addOption('link', 'Previous Week',
            "?a=corp_stats&amp;daterange=weekly&w={$prev_week}&y={$prev_year}" . $url_ext);

        if (($next_year > (int)date('Y')) || (($next_year == (int)date('Y')) && ($next_week <=
            (int)kbdate('W') && true))) {
            $menubox->addOption('link', 'Next Week', "?a=corp_stats&amp;daterange=weekly&w={$next_week}&y={$next_year}" .
                $url_ext);
        }
    }

    if ($daterange_month) {
        $next_month = $month == 12 ? 1 : $month + 1;
        $next_year = $month == 12 ? $year + 1 : $year;

        $prev_month = $month == 1 ? 12 : $month - 1;
        $prev_year = $month == 1 ? $year - 1 : $year;

        $menubox->addOption('link', 'Previous Month',
            "?a=corp_stats&amp;daterange=monthly&m={$prev_month}&y={$prev_year}" . $url_ext);

        if (($next_year > (int)date('Y')) || (($next_year == (int)date('Y')) && ($next_month <=
            (int)date('m') && true))) {
            $menubox->addOption('link', 'Next Month',
                "?a=corp_stats&amp;daterange=monthly&m={$next_month}&y={$next_year}" . $url_ext);
        }
    }

    if ($daterange_year) {
        $next_year = $year + 1;
        $prev_year = $year - 1;

        //	if( $next_year >= date('Y') ) {
        $menubox->addOption('link', 'Previous Year',
            "?a=corp_stats&amp;daterange=yearly&y={$prev_year}" . $url_ext);
        //	}

        if ($next_year <= (int)date('Y')) {
            $menubox->addOption('link', 'Next Year', "?a=corp_stats&amp;daterange=yearly&y={$next_year}" .
                $url_ext);
        }
    }

}

$page->addContext($menubox->generate());

$page->generate();

?>
