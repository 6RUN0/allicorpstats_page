<?php

class AlliCorpStats
{
    //! Create a box and set the title.
    function AlliCorpStats($id)
    {
        $this->id = $id;
    }

    //! Generate the html from the template.
    function generate()
    {
        global $smarty;

        //count also all those usless shitheads as active
        $active_members_losses = true;

        $datefilter = $this->getDateFilter();

        //Start classes and update local api data
        if ($this->id > 99000001) {
            $alliance = new Alliance($this->id, true);
        } else {
            $alliance = new Alliance($this->id, false);
        }
        $tempMyCorp = new Corporation();
        $myAlliAPI = new API_Alliance();
        $myAlliAPI->fetchalliances();

        // Use alliance ID if we have it
        if ($alliance->getExternalID()) {
            $myAlliance = $myAlliAPI->LocateAllianceID($alliance->getExternalID());
        } else {
            $myAlliance = $myAlliAPI->LocateAlliance($alliance->getName());
        }
        //get the internal ID for sql queries
        $all_id = $alliance->getID();


        $myCorpAPI = new API_CorporationSheet();

        if ($myAlliance) {

            $str_no_n00bs = '';
            if (@$_GET['no_n00bs'] == 'true') {
                // Noobship, Shuttle, Capsule
                $str_no_n00bs = " AND st.shp_class NOT IN (3,11,2) ";
            }

            $corps = array();
            foreach ((array )$myAlliance['memberCorps'] as $tempcorp) {
                $myCorpAPI->setCorpID($tempcorp['corporationID']);
                if($myCorpAPI->fetchXML() === FALSE) {
                  continue;
                }

                // Check if corp is known to EDK DB, if not, add it. (hackign up to work with new classes in edk4)
                $tempMyCorp->Corporation();
                $this->corpid = $tempMyCorp->lookup($myCorpAPI->getCorporationName());
                if($this->corpid === FALSE) {
                  continue;
                }
                if ($this->corpid->getID() == 0) {
                    $tempMyCorp->add($myCorpAPI->getCorporationName(), $alliance, substr($tempcorp['startDate'],
                        0, 16));
                }
                //set the corp id for the next checks...cleaner
                $corp_id = $this->corpid->getID(); 

                //if corp size filtering is enable we skip based on the count set
                if ($limit = config::get('allicorpstatspage_filtermemcount') and $myCorpAPI->
                    getMemberCount() > $limit) {

                    // get a kill list, loss list and efficieny for each corp
                    // lets bypass EDK here and do the query ourselves
                    $qry = new DBQuery();

                    // TODO: FIX-ME!  kll_isk_loss can't be calculated like this -- distinct may be a problem
                    $sql = "SELECT count(distinct kll_id) as kills, sum(kll_isk_loss) as iskkill, count( DISTINCT ind_plt_id ) AS active_members
														FROM kb3_kills kll
														INNER JOIN kb3_inv_detail inv ON inv.ind_kll_id = kll.kll_id
														INNER JOIN kb3_ships st ON st.shp_id = kll.kll_ship_id 
														WHERE inv.ind_crp_id = {$corp_id} 
														AND inv.ind_all_id = {$all_id} 
														AND {$datefilter}
														{$str_no_n00bs} 
														";

                    $qry->execute($sql);
                    $killData = $qry->getRow();
                    $corps[$corp_id] = $corp_id;

                    $qry = new DBQuery();

                    $sql = "SELECT /*count(distinct kll_id)*/ kll_id, kll_isk_loss, st.shp_class
														FROM kb3_kills kll
														INNER JOIN kb3_inv_detail inv ON inv.ind_kll_id = kll.kll_id
														INNER JOIN kb3_ships st ON st.shp_id = kll.kll_ship_id 
														WHERE kll.kll_crp_id = {$corp_id} 
														AND kll.kll_all_id = {$all_id} 
														AND {$datefilter}
														{$str_no_n00bs} 
														GROUP BY kll_id
														";

                    $sql = "SELECT count(kll_id) as losses, sum(kll_isk_loss) as iskloss FROM ($sql) as st WHERE 1 {$str_no_n00bs} ";

                    $qry->execute($sql);

                    $lossData = $qry->getRow();

                    // Build Data array
                    $membercorp['corpName'] = $myCorpAPI->getCorporationName();
                    $membercorp['corpID'] = $corp_id;
                    $membercorp['corpKills'] = $killData['kills'];
                    $membercorp['corpIskKill'] = $killData['iskkill'] / 1000;
                    $membercorp['corpLosses'] = $lossData['losses'];
                    $membercorp['corpIskLoss'] = $lossData['iskloss'] / 1000;
                    $membercorp['active_members'] = $membercorp['active_members_real'] = (int)$killData['active_members'];

                    if (config::get('allicorpstatspage_eff') == 'killlosseff') {
                        $membercorp['corpEfficiency'] = round($killData['kills'] / (($killData['kills'] +
                            $lossData['losses']) == 0 ? 1 : ($killData['kills'] + $lossData['losses'])) *
                            100, 2);
                    } else {
                        $membercorp['corpEfficiency'] = round($killData['iskkill'] / (($killData['iskkill'] +
                            $lossData['iskloss']) == 0 ? 1 : ($killData['iskkill'] + $lossData['iskloss'])) *
                            100, 2);
                    }
                    $bar = new BarGraph($membercorp['corpEfficiency'], 100, 75);
                    $membercorp['corpBar'] = $bar->generate();
                    if (config::get('allicorpstatspage_ticker'))
                        $membercorp["ticker"] = $myCorpAPI->getTicker();
                    if (config::get('allicorpstatspage_members'))
                        $membercorp["members"] = $myCorpAPI->getMemberCount();
                    if (config::get('allicorpstatspage_ceo'))
                        $membercorp['corpCeo'] = $myCorpAPI->getCeoName();
                    if (config::get('allicorpstatspage_hq'))
                        $membercorp['corpHQ'] = $myCorpAPI->getStationName();

                    $membercorp['active_members_proz'] = $membercorp['active_members_proz_real'] = @($membercorp['active_members'] /
                        $membercorp['members']) * 100;

                    // add all the data together into another array
                    $AllianceCorps[$corp_id] = $membercorp;

                    $membercorp = array();
                    unset($membercorp);
                }
            }

            ///*****************///

            if (count($corps)) {

                $sql = "SELECT ind_crp_id, SUM(kll_isk_loss) AS iskkill 
							FROM (
								SELECT inv.ind_crp_id, kll_isk_loss, inv.ind_plt_id
								FROM kb3_kills kll
								INNER JOIN kb3_inv_detail inv ON inv.ind_kll_id = kll.kll_id
								INNER JOIN kb3_ships st ON st.shp_id = kll.kll_ship_id
								WHERE inv.ind_crp_id IN ( " . join(',', $corps) . " )
								AND inv.ind_all_id ={$all_id} 
								AND {$datefilter}
								{$str_no_n00bs} 
								GROUP BY kll.kll_id, inv.ind_crp_id
							) AS TMP
							GROUP BY ind_crp_id";
                #	print $sql;
                if (!$qry->execute($sql)) {
                    continue;
                }

                while ($row = $qry->getRow()) {
                    $newisk = $row['iskkill'] / 1000;
                    if (isset($AllianceCorps[$row['ind_crp_id']])) {
                        $AllianceCorps[$row['ind_crp_id']]['corpIskKill'] = $newisk;
                        if (config::get('allicorpstatspage_eff') == 'killlosseff') {
                        } else {
                            $AllianceCorps[$row['ind_crp_id']]['corpEfficiency'] = round($AllianceCorps[$row['ind_crp_id']]['corpIskKill'] /
                                (($AllianceCorps[$row['ind_crp_id']]['corpIskKill'] + $AllianceCorps[$row['ind_crp_id']]['corpIskLoss']) ==
                                0 ? 1 : ($AllianceCorps[$row['ind_crp_id']]['corpIskKill'] + $AllianceCorps[$row['ind_crp_id']]['corpIskLoss'])) *
                                100, 2);
                            $bar = new BarGraph($AllianceCorps[$row['ind_crp_id']]['corpEfficiency'], 100,
                                75);
                            $AllianceCorps[$row['ind_crp_id']]['corpBar'] = $bar->generate();
                        }
                    }
                }

                if ($active_members_losses) {
                    $sql = "SELECT COUNT(distinct charid) as active_members, corpid 
							FROM (
									SELECT kll.`kll_victim_id` as charid, kll.`kll_crp_id` AS corpid, 'losses' as `where`
									FROM `kb3_kills` kll 
									WHERE kll.`kll_crp_id` IN ( " . join(',', $corps) .
                        " ) AND kll.kll_all_id = '{$all_id}'
									AND  {$datefilter}
									GROUP BY kll.`kll_victim_id`, kll.`kll_crp_id`
								UNION 
									SELECT inv.`ind_plt_id` as charid, inv.`ind_crp_id` AS corpid, 'kills' as `where`
									FROM `kb3_inv_detail` inv
									inner join `kb3_kills` kll ON kll.kll_id = inv.`ind_kll_id`  AND  {$datefilter}
									WHERE inv.`ind_crp_id` IN ( " . join(',', $corps) .
                        " ) AND inv.ind_all_id = '{$all_id}' 
									GROUP BY inv.`ind_plt_id`, inv.`ind_crp_id`
							) AS TMP
							GROUP BY corpid
							";
                    #	print $sql;
                    if (!$qry->execute($sql)) {
                        continue;
                    }

                    while ($row = $qry->getRow()) {
                        if (isset($AllianceCorps[$row['corpid']])) {
                            $AllianceCorps[$row['corpid']]['active_members'] = $row['active_members'];
                            $AllianceCorps[$row['corpid']]['active_members_proz'] = @(($AllianceCorps[$row['corpid']]['active_members']) /
                                ($AllianceCorps[$row['corpid']]['members'])) * 100;
                        }
                    }
                }

            }

            ///*****************///

        } elseif ($myAlliName == 'Amarr Empire' || $myAlliName == 'Minmatar Republic' ||
        $myAlliName == 'Caldari State' || $myAlliName == 'Gallente Federation') {
            $qry = new DBQuery();
            $qry->execute("	SELECT crp_id, crp_name, crp_external_id
														FROM kb3_corps 
														WHERE crp_all_id = {$all_id}
													");

            $numRows = $qry->recordCount();

            for ($i = 0; $i < $numRows; ++$i) {
                $corpData = $qry->getRow();
                $membercorps[] = array('corpName' => $corpData['crp_name'], 'corpID' => $corpData['crp_id'],
                    'corpEVEID' => $corpData['crp_external_id']);
            }

            foreach ($membercorps as $corp) {
                $myCorpAPI->setCorpID($corp['corpEVEID']);
                if($myCorpAPI->fetchXML() === FALSE) {
                  continue;
                }

                // Check if corp is known to EDK DB, if not, add it.
                $tempMyCorp->Corporation();
                $tempMyCorp->lookup($myCorpAPI->getCorporationName());
                if ($corp_id == 0) {
                    $tempMyCorp->add($myCorpAPI->getCorporationName(), $alliance, substr($corp['startDate'],
                        0, 16));
                }

                $crp_id = $corp['corpID'];
                $qry = new DBQuery();
                $qry->execute("	SELECT count(distinct kll_id) as kills, sum(kll_isk_loss) as iskkill
																FROM kb3_kills kll
																INNER JOIN kb3_inv_detail inv ON inv.ind_kll_id = kll.kll_id
																WHERE inv.ind_crp_id = {$corp['corpID']}
																AND inv.ind_all_id = {$all_id} 
																AND {$datefilter}
															");
                $killData = $qry->getRow();

                $qry = new DBQuery();
                $qry->execute("	SELECT count(distinct kll_id) as losses, sum(kll_isk_loss) as iskloss
																FROM kb3_kills kll
																WHERE kll.kll_crp_id = {$corp['corpID']}
																AND kll.kll_all_id = {$all_id} 
																AND {$datefilter}
															");
                $lossData = $qry->getRow();

                // Build Data array
                $membercorp['corpName'] = $corp['corpName'];
                $membercorp['corpID'] = $corp['corpID'];
                $membercorp['corpKills'] = $killData['kills'];
                $membercorp['corpIskKill'] = $killData['iskkill'] / 1000;
                $membercorp['corpLosses'] = $lossData['losses'];
                $membercorp['corpIskLoss'] = $lossData['iskloss'] / 1000;
                if (config::get('bm_allicorpstats_eff') == 'killlosseff') {
                    $membercorp['corpEfficiency'] = round($killData['kills'] / (($killData['kills'] +
                        $lossData['losses']) == 0 ? 1 : ($killData['kills'] + $lossData['losses'])) *
                        100, 2);
                } else { // damagedone / (damagedone + damagereceived ) * 100
                    $membercorp['corpEfficiency'] = round($killData['iskkill'] / (($killData['iskkill'] +
                        $lossData['iskloss']) == 0 ? 1 : ($killData['iskkill'] + $lossData['iskloss'])) *
                        100, 2);
                    #	$membercorp['corpEfficiency'] = (( $killData['iskkill'] / ($killData['iskkill'] + $lossData['iskloss']) ) * 100 );
                }


                $bar = new BarGraph($membercorp['corpEfficiency'], 100, 75);
                $membercorp['corpBar'] = $bar->generate();
                if (config::get('allicorpstatspage_ticker'))
                    $membercorp["ticker"] = $myCorpAPI->getTicker();
                if (config::get('allicorpstatspage_members'))
                    $membercorp["members"] = $myCorpAPI->getMemberCount();
                if (config::get('allicorpstatspage_ceo'))
                    $membercorp['corpCeo'] = $myCorpAPI->getCeoName();
                if (config::get('allicorpstatspage_hq'))
                    $membercorp['corpHQ'] = $myCorpAPI->getStationName();

                // add all the data together into another array
                $AllianceCorps[] = $membercorp;

                $membercorp = array();
                unset($membercorp);
            }
        }

        $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpName');

        // order by settings, pulled from allicorpstats_page settings.php
        // we have to order the array in php rather than order the SQL query. this is
        // because we dont pull names, ids or efficiency in our own query
        $_order = config::get('allicorpstatspage_order');


        foreach ($AllianceCorps as & $_alycorp) {
            $killrq = (int)((int)$_alycorp['members'] > 66 ? (int)$_alycorp['members'] * 4 :
                200);
            $killrq = (int)$_alycorp['members'] * 4;
            $_alycorp['killrq_base'] = $killrq;
            $_alycorp['killrq'] = $_alycorp['corpKills'] - $killrq;
            $_alycorp['killrq_nq'] = $_alycorp['killrq'] < 0;

            $_alycorp['killrq_status_member_act'] = ($_alycorp['active_members_proz'] > 24);


            if (!$_alycorp['killrq_status_member_act'] && $_alycorp['killrq_nq']) {
                $_alycorp['killrq_status'] = 'bad';
                $_alycorp['killrq_status_color'] = '#B11818';
            } elseif (!$_alycorp['killrq_status_member_act']) {
                $_alycorp['killrq_status'] = 'bad_act';
                $_alycorp['killrq_status_color'] = 'yellow';
            } elseif ($_alycorp['killrq_nq']) {
                $_alycorp['killrq_status'] = 'bad_killrq';
                $_alycorp['killrq_status_color'] = 'orange';
            } else {
                $_alycorp['killrq_status'] = 'ok';
                $_alycorp['killrq_status_color'] = '#6FAC60';
            }

            if (!empty($this->weekno_) && (@$_GET['daterange'] == 'weekly')) {
                $_alycorp['killrq_status'] = 'weekly';
                $_alycorp['killrq_status_color'] = '#E0E0E0';
            }

            $_alycorp['f_title'] = '';
            if ($_alycorp['killrq_nq']) {
                $_alycorp['f_title'] .= " ( " . ((int)(@($_alycorp['corpKills'] / $_alycorp['killrq_base']) *
                    100)) . '%';

                if ((int)$this->monthno_ == (int)date('m') && (int)$this->yearno_ == (int)date('Y')) {
                    $_alycorp['f_title'] .= " | " . ((int)(@(date('j') / date('t')) * 100)) . '%';
                }

                $_alycorp['f_title'] .= " ) ";
            }

        }

        if (isset($_GET['order']) && in_array($_GET['order'], array('nameasc',
            'tickerasc', 'ceodesc', 'membersdesc', 'memberactsdesc', 'memberactprozsdesc',
            'nameasc', 'killsdesc', 'killiskdesc', 'lossesdesc', 'lossiskdesc', 'effdesc',
            'killrq'))) {
            $_order = $_GET['order'];
        }

        if ($_order == 'membersdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'members', arsort);
        if ($_order == 'memberactsdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'active_members', arsort);
        if ($_order == 'memberactprozsdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'active_members_proz', arsort);


        if ($_order == 'tickerasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'ticker');
        if ($_order == 'ceodesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpCeo');

        if ($_order == 'nameasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpName');
        if ($_order == 'namedesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpName', arsort);
        if ($_order == 'killsasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpKills');
        if ($_order == 'killsdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpKills', arsort);
        if ($_order == 'killiskasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpIskKill');
        if ($_order == 'killiskdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpIskKill', arsort);
        if ($_order == 'lossesasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpLosses');
        if ($_order == 'lossesdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpLosses', arsort);
        if ($_order == 'lossiskasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpIskLoss');
        if ($_order == 'lossiskdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpIskLoss', arsort);
        if ($_order == 'effasc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpEfficiency');
        if ($_order == 'effdesc')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'corpEfficiency', arsort);
        if ($_order == 'killrq')
            $AllianceCorps = $this->subvalSort($AllianceCorps, 'killrq');

        $smarty->assign('order', $_order);

        #	echo '<pre>'.print_r($AllianceCorps,1).'</pre>';

        if (config::get('allicorpstatspage_datefilter') == 'weekly') {
            $smarty->assign('datefilter', "Week {$this->weekno_}." . " {$this->yearno_}");
        } elseif (config::get('allicorpstatspage_datefilter') == 'monthly') {
            $timestamp = mktime(0, 0, 0, $this->monthno_, 1, 2005);
            $smarty->assign('datefilter', date("F", $timestamp) . " {$this->yearno_}");
        } elseif (config::get('allicorpstatspage_datefilter') == 'yearly') {
            $smarty->assign('datefilter', "{$this->yearno_}");
        } elseif (config::get('allicorpstatspage_datefilter') == 'alltime') {
            $smarty->assign('datefilter', "All-Time");
        }

        if (isset($_GET['daterange']) && $_GET['daterange'] == 'weekly') {
            $smarty->assign('datefilter', "Week {$this->weekno_}." . " {$this->yearno_}");
        } elseif (isset($_GET['daterange']) && $_GET['daterange'] == 'monthly') {
            $timestamp = mktime(0, 0, 0, $this->monthno_, 1, 2005);
            $smarty->assign('datefilter', date("F", $timestamp) . " {$this->yearno_}");
        } elseif (isset($_GET['daterange']) && $_GET['daterange'] == 'yearly') {
            $smarty->assign('datefilter', "{$this->yearno_}");
        } elseif (isset($_GET['daterange']) && $_GET['daterange'] == 'alltime') {
            $smarty->assign('datefilter', "All-Time");
        }

        if (config::get('allicorpstatspage_ticker'))
            $smarty->assign('showticker', 1);
        if (config::get('allicorpstatspage_members'))
            $smarty->assign('showmembers', 1);
        if (config::get('allicorpstatspage_ceo'))
            $smarty->assign('showceo', 1);
        if (config::get('allicorpstatspage_hq'))
            $smarty->assign('showhq', 1);
        $smarty->assign('membercorps', $AllianceCorps);

        $ext_link = (!empty($_GET['w']) ? '&w=' . (int)$_GET['w'] : '') . (!empty($_GET['m']) ?
            '&m=' . (int)$_GET['m'] : '') . (!empty($_GET['y']) ? '&y=' . (int)$_GET['y'] :
            '') . (@$_GET['no_n00bs'] == 'true' ? '&no_n00bs=true' : '');

        $smarty->assign('ext_link', $ext_link);

        $smarty->assign('active_members_losses', $active_members_losses);

        return $smarty->fetch(getcwd() . '/mods/allicorpstats_page/allicorpstats.tpl');
    }

    //! Filter results by week. Requires the year to also be set.
    function setWeek($weekno)
    {
        $weekno = intval($weekno);
        if ($weekno < 1)
            $this->weekno_ = 1;
        if ($weekno > 53)
            $this->weekno_ = 53;
        else
            $this->weekno_ = $weekno;
    }

    //! Filter results by year.
    function setYear($yearno)
    {
        // 1970-2038 is the allowable range for the timestamp code used
        // Needs to be revisited in the next 30 years
        $yearno = intval($yearno);
        if ($yearno < 1970)
            $this->yearno_ = 1970;
        if ($yearno > 2038)
            $this->yearno_ = 2038;
        else
            $this->yearno_ = $yearno;
    }

    //! Filter results by month
    function setMonth($monthno)
    {
        $monthno = intval($monthno);
        if ($monthno < 1)
            $this->monthno_ = 1;
        if ($monthno > 12)
            $this->monthno_ = 12;
        else
            $this->monthno_ = $monthno;
    }

    //! Filter results by starting week. Requires the year to also be set.
    function setStartWeek($weekno)
    {
        $weekno = intval($weekno);
        if ($weekno < 1)
            $this->startweekno_ = 1;
        if ($weekno > 53)
            $this->startweekno_ = 53;
        else
            $this->startweekno_ = $weekno;
    }

    //! Filter results by starting date/time.
    function setStartDate($timestamp)
    {
        // Check timestamp is valid before adding
        if (strtotime($timestamp))
            $this->startDate_ = $timestamp;
    }

    //! Filter results by ending date/time.
    function setEndDate($timestamp)
    {
        // Check timestamp is valid before adding
        if (strtotime($timestamp))
            $this->endDate_ = $timestamp;
    }

    //! \return string containing SQL date filter.
    function getDateFilter()
    {
        $qstartdate = makeStartDate($this->weekno_, $this->yearno_, $this->monthno_, $this->
            startweekno_, $this->startDate_);
        $qenddate = makeEndDate($this->weekno_, $this->yearno_, $this->monthno_, $this->
            endDate_);
        if ($qstartdate)
            $sql .= " kll.kll_timestamp >= '" . gmdate('Y-m-d H:i', $qstartdate) . "' ";
        if ($qstartdate && $qenddate)
            $sql .= " AND ";
        if ($qenddate)
            $sql .= " kll.kll_timestamp <= '" . gmdate('Y-m-d H:i', $qenddate) . "' ";
        return $sql;
    }

    function subvalSort($a, $subkey, $sort = asort)
    {
        foreach ($a as $k => $v) {
            $b[$k] = strtolower($v[$subkey]);
        }
        $sort($b);

        foreach ($b as $key => $val) {
            $c[] = $a[$key];
        }
        return $c;
    }
}
?>
