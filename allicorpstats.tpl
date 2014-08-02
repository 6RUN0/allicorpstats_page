<div class=kb-campaigns-header>Member Corp Statistics for {$datefilter}</div> 

{assign var='bgac' value='style="background:url(mods/allicorpstats_page/active.gif) repeat scroll 0 0 #003000;"'}

<table class="kb-table" width="99%" align="center" cellspacing="1">
 <tr class="kb-table-header">
	<td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=nameasc">Corp Name</a></td>
	{if $showticker > 0}
	<td class="kb-table-cell"align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=tickerasc">Ticker</a></td>
	{/if}
	{if $showmembers > 0}
	<td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=membersdesc">Members</a></td>
	<td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=memberactsdesc">Active Members</a></td>
	<td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=memberactprozsdesc">%</a></td>	
	{/if}
	{if $showceo > 0}
	<td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=ceodesc">CEO</a></td>
	{/if}
	{if $showhq > 0}
	<td class="kb-table-cell" align="center">HQ</td>
	{/if}
  <td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=killsdesc">Kills</a></td>
{if $killrq}  
	<td {* {if $order eq 'killrq'}{$bgac}{/if} *} class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=killrq"><span style="white-space:nowrap;">Kill Rq.</span></a></td>
{/if}
  <td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=killiskdesc">ISK (B)</a></td>
  <td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=lossesdesc">Losses</a></td>
  <td class="kb-table-cell" align="center"><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=lossiskdesc">ISK (B)</a></td>
  <td class="kb-table-cell" align="center" colspan=2><a class="corp-stat-header" href="?a={$smarty.get.a}{if $smarty.get.daterange}&daterange={$smarty.get.daterange}{/if}{$ext_link}&order=effdesc">Efficiency</a></td>
 </tr>
 
{cycle reset=true print=false name=ccl values="kb-table-row-even,kb-table-row-odd"}
{foreach from=$membercorps item=i}
 <tr class="{cycle advance=false name=ccl}" onmouseover="this.className='kb-table-row-hover';"
    onmouseout="this.className='{cycle name=ccl}';" onClick="window.location.href='?a=corp_detail&amp;crp_id={$i.corpID}';">
  <td class="kb-table-cell"><b>{$i.corpName}</b> <br> </td>
	{if $showticker > 0}
	<td class="kb-table-cell" align="center">{$i.ticker}</td>
	{/if}
	{if $showmembers > 0}
	<td class="kb-table-cell" align="center">{$i.members}</td>
	<td {* class="kb-table-cell" *} class="{if $i.killrq_status_member_act}kl-kill{else}kl-loss{/if}" {if $active_members_losses} title="{$i.active_members_real}/{$i.active_members} ({$i.active_members_proz_real|string_format:"%.0f"}%)" {/if} align="center">{$i.active_members}</td>
	<td {* class="kb-table-cell" *} class="{if $i.killrq_status_member_act}kl-kill{else}kl-loss{/if}" align="center">{$i.active_members_proz|string_format:"%.0f"}%</td>
	{/if}
	{if $showceo > 0}
	<td class="kb-table-cell" align="center">{$i.corpCeo}</td>
	{/if}
	{if $showhq > 0}
	<td class="kb-table-cell" align="center">{$i.corpHQ}</td>
	{/if}
  <td class="kl-kill" align="center">{$i.corpKills}</td>
{if $killrq}   
	<td {* {if $order eq 'killrq'}{$bgac}{/if} *} class="{if $i.killrq_nq}kl-loss{else}kl-kill{/if}" align="center"><span title="{$i.corpKills} - {$i.killrq_base} = {$i.killrq} {$i.f_title}"><!--{if $i.killrq gt 0}+{/if}-->{$i.killrq}</span></td>
{/if}
  <td class="kl-kill" align="center">{($i.corpIskKill/1000000)|string_format:"%.2f"}</td>
  <td class="kl-loss" align="center">{$i.corpLosses}</td>
  <td class="kl-loss" align="center">{($i.corpIskLoss/1000000)|string_format:"%.2f"}</td>
  <td class="kb-table-cell" align="center" width="50"><b>{$i.corpEfficiency}%</b></td>
  <td class="kb-table-cell" align="left" width="75">{$i.corpBar}</td>
 </tr>
{/foreach}
</table>
{*
<i><b>Active members</b> - is member who has any kind of kb history in selected period of time (both kills and losses)</i>
*}
{if $no_n00bs}
<br><i>Noobships, Shuttles and Capsules are removed from statistics.</i>
{/if}
