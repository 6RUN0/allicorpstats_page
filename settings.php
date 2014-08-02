<?php
require_once('common/admin/admin_menu.php');

$page = new Page("Settings - Alliance Corp Statistics Page");

if ($_POST['submit'])
{
	config::set('allicorpstatspage_enable', $_POST['allicorpstatspage_enable']);
	if ($_POST['allicorpstatspage_enable'])
	{
			// add nav bar entry
			$qry = new DBQuery();
			$qry->execute("SELECT url FROM kb3_navigation WHERE KBSITE = '".KB_SITE."' AND url = '?a=corp_stats'");
			if ($qry->recordCount() == 0)
			{
					$qry = new DBQuery();
					$qry->execute("INSERT INTO kb3_navigation (nav_type, intern, descr, url, target, posnr, page, hidden, KBSITE) VALUES ('top', 1, 'Corp Stats', '?a=corp_stats', '_self', 6, 'ALL_PAGES', 0, '".KB_SITE."')");
			}
			else
			{
					$qry = new DBQuery();
					$qry->execute("UPDATE kb3_navigation SET hidden = 0 WHERE url = '?a=corp_stats' AND KBSITE = '".KB_SITE."'");
			}
	}
	else
	{
			$qry = new DBQuery();
			$qry->execute("SELECT url FROM kb3_navigation WHERE KBSITE = '".KB_SITE."' AND url = '?a=corp_stats'");
			if ($qry->recordCount() > 0)
			{
					$qry = new DBQuery();
					$qry->execute("UPDATE kb3_navigation SET hidden = 1 WHERE url = '?a=corp_stats' AND KBSITE = '".KB_SITE."'");
			}
	}
	config::set('allicorpstatspage_eff', $_POST['allicorpstatspage_eff']);
	config::set('allicorpstatspage_datefilter', $_POST['allicorpstatspage_datefilter']);
	config::set('allicorpstatspage_order', $_POST['allicorpstatspage_order']);
	config::set('allicorpstatspage_ticker', $_POST['allicorpstatspage_ticker']);
	config::set('allicorpstatspage_members', $_POST['allicorpstatspage_members']);
	config::set('allicorpstatspage_ceo', $_POST['allicorpstatspage_ceo']);
	config::set('allicorpstatspage_hq', $_POST['allicorpstatspage_hq']);
    config::set('allicorpstatspage_filtermemcount', $_POST['allicorpstatspage_filtermemcount']);
	$html .= "Settings Saved";
}

// get the value to populate the page
$allicorpstatspage_enable = config::get('allicorpstatspage_enable');
$allicorpstatspage_eff = config::get('allicorpstatspage_eff');
$allicorpstatspage_datefilter = config::get('allicorpstatspage_datefilter');
$allicorpstatspage_order = config::get('allicorpstatspage_order');
$allicorpstatspage_ticker = config::get('allicorpstatspage_ticker');
$allicorpstatspage_members = config::get('allicorpstatspage_members');
$allicorpstatspage_ceo = config::get('allicorpstatspage_ceo');
$allicorpstatspage_hq = config::get('allicorpstatspage_hq');
$allicorpstatspage_filtermemcount = config::get('allicorpstatspage_filtermemcount');

// start the output display
$html .= "<form id=options name=options method=post action=>";

$html .= "<div class=block-header2>General Options</div>";
$html .= "<table class=kb-subtable width=\"360\" border=\"0\" cellspacing=\"1\">";
$html .= "<tr><td width=300><b>Enable Alliance Corp Statistics Page</b><p>(adds/removes entry to nav bar)</p></td><td><input type=checkbox name=allicorpstatspage_enable";
if ($allicorpstatspage_enable) $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Calculate Efficiency by ISK</b></td><td><input type=radio name=allicorpstatspage_eff value=iskeff";
if ($allicorpstatspage_eff == 'iskeff') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Calculate Efficiency by Kills/Losses</b></td><td><input type=radio name=allicorpstatspage_eff value=killlosseff";
if ($allicorpstatspage_eff == 'killlosseff') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Corp Member Limit</b><p>(only shows corp with minimum set number or 0 to disable)</p></td><td><input type=textarea name=allicorpstatspage_filtermemcount size=\"2\"";
if ($allicorpstatspage_filtermemcount) $html .= " value=\"$allicorpstatspage_filtermemcount\"";
$html .= "></td></tr>";
$html .= "</table><br />";

$html .= "<div class=block-header2>Datefilter Options</div>";
$html .= "<table class=kb-subtable width=\"360\" border=\"0\" cellspacing=\"1\">";
$html .= "<tr><td width=300><b>Weekly Corp Statistics</b></td><td><input type=radio name=allicorpstatspage_datefilter value=weekly";
if ($allicorpstatspage_datefilter == 'weekly') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Monthly Corp Statistics</b></td><td><input type=radio name=allicorpstatspage_datefilter value=monthly";
if ($allicorpstatspage_datefilter == 'monthly') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Yearly Corp Statistics</b></td><td><input type=radio name=allicorpstatspage_datefilter value=yearly";
if ($allicorpstatspage_datefilter == 'yearly') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>All-Time Corp Statistics</b></td><td><input type=radio name=allicorpstatspage_datefilter value=alltime";
if ($allicorpstatspage_datefilter == 'alltime') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "</table><br />";

$html .= "<div class=block-header2>Order By Options</div>";
$html .= "<table class=kb-subtable width=\"360\" border=\"0\" cellspacing=\"1\">";
$html .= "<tr><td width=300></td><td>ASC</td><td>DESC</td></tr>";
$html .= "<tr><td width=300><b>Order by Name</b></td><td><input type=radio name=allicorpstatspage_order value=nameasc";
if ($allicorpstatspage_order == 'nameasc') $html .= " checked=\"checked\"";
$html .= "></td><td><input type=radio name=allicorpstatspage_order value=namedesc";
if ($allicorpstatspage_order == 'namedesc') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Order by Kills</b></td><td><input type=radio name=allicorpstatspage_order value=killsasc";
if ($allicorpstatspage_order == 'killsasc') $html .= " checked=\"checked\"";
$html .= "></td><td><input type=radio name=allicorpstatspage_order value=killsdesc";
if ($allicorpstatspage_order == 'killsdesc') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Order by Kill ISK value</b></td><td><input type=radio name=allicorpstatspage_order value=killiskasc";
if ($allicorpstatspage_order == 'killiskasc') $html .= " checked=\"checked\"";
$html .= "></td><td><input type=radio name=allicorpstatspage_order value=killiskdesc";
if ($allicorpstatspage_order == 'killiskdesc') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Order by Losses</b></td><td><input type=radio name=allicorpstatspage_order value=lossesasc";
if ($allicorpstatspage_order == 'lossesasc') $html .= " checked=\"checked\"";
$html .= "></td><td><input type=radio name=allicorpstatspage_order value=lossesdesc";
if ($allicorpstatspage_order == 'lossesdesc') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Order by Loss ISK value</b></td><td><input type=radio name=allicorpstatspage_order value=lossiskasc";
if ($allicorpstatspage_order == 'lossiskasc') $html .= " checked=\"checked\"";
$html .= "></td><td><input type=radio name=allicorpstatspage_order value=lossiskdesc";
if ($allicorpstatspage_order == 'lossiskdesc') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Order by Efficiency</b></td><td><input type=radio name=allicorpstatspage_order value=effasc";
if ($allicorpstatspage_order == 'effasc') $html .= " checked=\"checked\"";
$html .= "></td><td><input type=radio name=allicorpstatspage_order value=effdesc";
if ($allicorpstatspage_order == 'effdesc') $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "</table><br />";

$html .= "<table class=kb-subtable width=\"360\" border=\"0\" cellspacing=\"1\">";
$html .= "<tr><td width=300><b>Display Corp Ticker</b></td><td><input type=checkbox name=allicorpstatspage_ticker";
if ($allicorpstatspage_ticker) $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Display Corp Member Count</b></td><td><input type=checkbox name=allicorpstatspage_members";
if ($allicorpstatspage_members) $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Display Corp CEO</b></td><td><input type=checkbox name=allicorpstatspage_ceo";
if ($allicorpstatspage_ceo) $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "<tr><td width=300><b>Display Corp HQ</b></td><td><input type=checkbox name=allicorpstatspage_hq";
if ($allicorpstatspage_hq) $html .= " checked=\"checked\"";
$html .= "></td></tr>";
$html .= "</table><br />";

$html .= "<table class=kb-subtable style=\"margin-top:10px;\"><tr><td width=120></td><td colspan=3 ><input type=submit name=submit value=\"Save\"></td></tr></table>";

$html .= "</form>";

$page->setContent($html);
$page->addContext($menubox->generate());
$page->generate();
?>