<?php
# call_report_export.php
# 
# displays options to select for downloading of leads and their vicidial_log 
# and/or vicidial_closer_log information by status, list_id and date range. 
# downloads to a flat text file that is tab delimited
#
# Copyright (C) 2010  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90310-2247 - First build
# 90330-1343 - Added more debug info, bug fixes
# 90508-0644 - Changed to PHP long tags
# 90721-1137 - Added rank and owner as vicidial_list fields
# 91121-0253 - Added list name, list description and status name
# 100119-1039 - Filtered comments for \n newlines
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
#

require("dbconnect.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["status"]))					{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))		{$status=$_POST["status"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["run_export"]))				{$run_export=$_GET["run_export"];}
	elseif (isset($_POST["run_export"]))	{$run_export=$_POST["run_export"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($shift)<2) {$shift='ALL';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin FROM system_settings;";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysql_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysql_fetch_row($rslt);
	$non_latin =	$row[0];
	}
##### END SETTINGS LOOKUP #####
###########################################

$PHP_AUTH_USER = ereg_replace("[^-_0-9a-zA-Z]","",$PHP_AUTH_USER);
$PHP_AUTH_PW = ereg_replace("[^-_0-9a-zA-Z]","",$PHP_AUTH_PW);

$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and pass='$PHP_AUTH_PW' and user_level > 7 and export_reports='1';";
if ($DB) {echo "|$stmt|\n";}
if ($non_latin > 0) { $rslt=mysql_query("SET NAMES 'UTF8'");}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$auth=$row[0];

if( (strlen($PHP_AUTH_USER)<2) or (strlen($PHP_AUTH_PW)<2) or (!$auth))
	{
#	Header("WWW-Authenticate: Basic realm=\"VICI-PROJECTS\"");
#	Header("HTTP/1.0 401 Unauthorized");
    echo "Invalid Username/Password or no export report permission: |$PHP_AUTH_USER|\n";
    exit;
	}


##### START RUN THE EXPORT AND OUTPUT FLAT DATA FILE #####
if ($run_export > 0)
	{
	$US='_';
	$MT[0]='';
	$ip = getenv("REMOTE_ADDR");
	$NOW_DATE = date("Y-m-d");
	$NOW_TIME = date("Y-m-d H:i:s");
	$FILE_TIME = date("Ymd-His");
	$STARTtime = date("U");
	if (!isset($group)) {$group = '';}
	if (!isset($query_date)) {$query_date = $NOW_DATE;}
	if (!isset($end_date)) {$end_date = $NOW_DATE;}

	$campaign_ct = count($campaign);
	$group_ct = count($group);
	$user_group_ct = count($user_group);
	$list_ct = count($list_id);
	$status_ct = count($status);
	$campaign_string='|';
	$group_string='|';
	$user_group_string='|';
	$list_string='|';
	$status_string='|';

	$i=0;
	while($i < $campaign_ct)
		{
		$campaign_string .= "$campaign[$i]|";
		$campaign_SQL .= "'$campaign[$i]',";
		$i++;
		}
	if ( (ereg("--NONE--",$campaign_string) ) or ($campaign_ct < 1) )
		{
		$campaign_SQL = "campaign_id IN('')";
		$RUNcampaign=0;
		}
	else
		{
		$campaign_SQL = eregi_replace(",$",'',$campaign_SQL);
		$campaign_SQL = "and vl.campaign_id IN($campaign_SQL)";
		$RUNcampaign++;
		}

	$i=0;
	while($i < $group_ct)
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$i++;
		}
	if ( (ereg("--NONE--",$group_string) ) or ($group_ct < 1) )
		{
		$group_SQL = "''";
		$group_SQL = "campaign_id IN('')";
		$RUNgroup=0;
		}
	else
		{
		$group_SQL = eregi_replace(",$",'',$group_SQL);
		$group_SQL = "and vl.campaign_id IN($group_SQL)";
		$RUNgroup++;
		}

	$i=0;
	while($i < $user_group_ct)
		{
		$user_group_string .= "$user_group[$i]|";
		$user_group_SQL .= "'$user_group[$i]',";
		$i++;
		}
	if ( (ereg("--ALL--",$user_group_string) ) or ($user_group_ct < 1) )
		{
		$user_group_SQL = "";
		}
	else
		{
		$user_group_SQL = eregi_replace(",$",'',$user_group_SQL);
		$user_group_SQL = "and vl.user_group IN($user_group_SQL)";
		}

	$i=0;
	while($i < $list_ct)
		{
		$list_string .= "$list_id[$i]|";
		$list_SQL .= "'$list_id[$i]',";
		$i++;
		}
	if ( (ereg("--ALL--",$list_string) ) or ($list_ct < 1) )
		{
		$list_SQL = "";
		}
	else
		{
		$list_SQL = eregi_replace(",$",'',$list_SQL);
		$list_SQL = "and vi.list_id IN($list_SQL)";
		}

	$i=0;
	while($i < $status_ct)
		{
		$status_string .= "$status[$i]|";
		$status_SQL .= "'$status[$i]',";
		$i++;
		}
	if ( (ereg("--ALL--",$status_string) ) or ($status_ct < 1) )
		{
		$status_SQL = "";
		}
	else
		{
		$status_SQL = eregi_replace(",$",'',$status_SQL);
		$status_SQL = "and vl.status IN($status_SQL)";
		}


	if ($DB > 0)
		{
		echo "<BR>\n";
		echo "$campaign_ct|$campaign_string|$campaign_SQL\n";
		echo "<BR>\n";
		echo "$group_ct|$group_string|$group_SQL\n";
		echo "<BR>\n";
		echo "$user_group_ct|$user_group_string|$user_group_SQL\n";
		echo "<BR>\n";
		echo "$list_ct|$list_string|$list_SQL\n";
		echo "<BR>\n";
		echo "$status_ct|$status_string|$status_SQL\n";
		echo "<BR>\n";
		}

	$outbound_calls=0;
	$export_rows='';
	$k=0;
	if ($RUNcampaign > 0)
		{
		$stmt = "SELECT vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.alt_dial,vi.rank,vi.owner,vi.lead_id from vicidial_users vu,vicidial_log vl,vicidial_list vi where vl.call_date >= '$query_date 00:00:00' and vl.call_date <= '$end_date 23:59:59' and vu.user=vl.user and vi.lead_id=vl.lead_id $list_SQL $campaign_SQL $user_group_SQL $status_SQL order by vl.call_date limit 100000;";
		$rslt=mysql_query($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$outbound_to_print = mysql_num_rows($rslt);
		if ($outbound_to_print < 1)
			{
			echo "There are no outbound calls during this time period for these parameters\n";
			exit;
			}
		else
			{
			$i=0;
			while ($i < $outbound_to_print)
				{
				$row=mysql_fetch_row($rslt);

				$row[29] = preg_replace("/\n|\r/",'!N',$row[29]);

				$export_status[$k] =	$row[2];
				$export_list_id[$k] =	$row[8];
				$export_rows[$k] = "$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]\t$row[7]\t$row[8]\t$row[9]\t$row[10]\t$row[11]\t$row[12]\t$row[13]\t$row[14]\t$row[15]\t$row[16]\t$row[17]\t$row[18]\t$row[19]\t$row[20]\t$row[21]\t$row[22]\t$row[23]\t$row[24]\t$row[25]\t$row[26]\t$row[27]\t$row[28]\t$row[29]\t$row[30]\t$row[31]\t$row[32]\t$row[33]\t$row[34]\t$row[35]\t";
				$i++;
				$k++;
				$outbound_calls++;
				}
			}
		}

	if ($RUNgroup > 0)
		{
		$stmtA = "SELECT vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.queue_seconds,vi.rank,vi.owner,vi.lead_id from vicidial_users vu,vicidial_closer_log vl,vicidial_list vi where vl.call_date >= '$query_date 00:00:00' and vl.call_date <= '$end_date 23:59:59' and vu.user=vl.user and vi.lead_id=vl.lead_id $list_SQL $group_SQL $user_group_SQL $status_SQL order by vl.call_date limit 100000;";
		$rslt=mysql_query($stmtA, $link);
		if ($DB) {echo "$stmt\n";}
		$inbound_to_print = mysql_num_rows($rslt);
		if ( ($inbound_to_print < 1) and ($outbound_calls < 1) )
			{
			echo "There are no inbound calls during this time period for these parameters\n";
			exit;
			}
		else
			{
			$i=0;
			while ($i < $inbound_to_print)
				{
				$row=mysql_fetch_row($rslt);

				$row[29] = preg_replace("/\n|\r/",'!N',$row[29]);

				$export_status[$k] =	$row[2];
				$export_list_id[$k] =	$row[8];
				$export_rows[$k] = "$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]\t$row[7]\t$row[8]\t$row[9]\t$row[10]\t$row[11]\t$row[12]\t$row[13]\t$row[14]\t$row[15]\t$row[16]\t$row[17]\t$row[18]\t$row[19]\t$row[20]\t$row[21]\t$row[22]\t$row[23]\t$row[24]\t$row[25]\t$row[26]\t$row[27]\t$row[28]\t$row[29]\t$row[30]\t$row[31]\t$row[32]\t$row[33]\t$row[34]\t$row[35]\t";
				$i++;
				$k++;
				}
			}
		}


	if ( ($outbound_to_print > 0) or ($inbound_to_print > 0) )
		{
		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtA|";
		$SQL_log = ereg_replace(';','',$SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LEADS', event_type='EXPORT', record_id='', event_code='ADMIN EXPORT CALLS REPORT', event_sql=\"$SQL_log\", event_notes='';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_query($stmt, $link);

		$TXTfilename = "EXPORT_CALL_REPORT_$FILE_TIME.txt";

		// We'll be outputting a TXT file
		header('Content-type: application/octet-stream');

		// It will be called LIST_101_20090209-121212.txt
		header("Content-Disposition: attachment; filename=\"$TXTfilename\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();

		$i=0;
		while ($k > $i)
			{
			$ex_list_name='';
			$ex_list_description='';
			$stmt = "SELECT list_name,list_description FROM vicidial_lists where list_id='$export_list_id[$i]';";
			$rslt=mysql_query($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$ex_list_ct = mysql_num_rows($rslt);
			if ($ex_list_ct > 0)
				{
				$row=mysql_fetch_row($rslt);
				$ex_list_name =			$row[0];
				$ex_list_description =	$row[1];
				}

			$ex_status_name='';
			$stmt = "SELECT status_name FROM vicidial_statuses where status='$export_status[$i]';";
			$rslt=mysql_query($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$ex_list_ct = mysql_num_rows($rslt);
			if ($ex_list_ct > 0)
				{
				$row=mysql_fetch_row($rslt);
				$ex_status_name =			$row[0];
				}
			else
				{
				$stmt = "SELECT status_name FROM vicidial_campaign_statuses where status='$export_status[$i]';";
				$rslt=mysql_query($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$ex_list_ct = mysql_num_rows($rslt);
				if ($ex_list_ct > 0)
					{
					$row=mysql_fetch_row($rslt);
					$ex_status_name =			$row[0];
					}
				}

			echo "$export_rows[$i]$ex_list_name\t$ex_list_description\t$ex_status_name\r\n";
			$i++;
			}
		}
	else
		{
		echo "There are no calls during this time period for these parameters\n";
		exit;
		}
	}
##### END RUN THE EXPORT AND OUTPUT FLAT DATA FILE #####


else
	{
	$NOW_DATE = date("Y-m-d");
	$NOW_TIME = date("Y-m-d H:i:s");
	$STARTtime = date("U");
	if (!isset($group)) {$group = '';}
	if (!isset($query_date)) {$query_date = $NOW_DATE;}
	if (!isset($end_date)) {$end_date = $NOW_DATE;}

	$stmt="select campaign_id from vicidial_campaigns order by campaign_id;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$campaigns_to_print = mysql_num_rows($rslt);
	$i=0;
		$LISTcampaigns[$i]='---NONE---';
		$i++;
		$campaigns_to_print++;
	while ($i < $campaigns_to_print)
		{
		$row=mysql_fetch_row($rslt);
		$LISTcampaigns[$i] =$row[0];
		$i++;
		}

	$stmt="select group_id from vicidial_inbound_groups order by group_id;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$groups_to_print = mysql_num_rows($rslt);
	$i=0;
		$LISTgroups[$i]='---NONE---';
		$i++;
		$groups_to_print++;
	while ($i < $groups_to_print)
		{
		$row=mysql_fetch_row($rslt);
		$LISTgroups[$i] =$row[0];
		$i++;
		}

	$stmt="select user_group from vicidial_user_groups order by user_group;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$user_groups_to_print = mysql_num_rows($rslt);
	$i=0;
		$LISTuser_groups[$i]='---ALL---';
		$i++;
		$user_groups_to_print++;
	while ($i < $user_groups_to_print)
		{
		$row=mysql_fetch_row($rslt);
		$LISTuser_groups[$i] =$row[0];
		$i++;
		}

	$stmt="select list_id from vicidial_lists order by list_id;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$lists_to_print = mysql_num_rows($rslt);
	$i=0;
		$LISTlists[$i]='---ALL---';
		$i++;
		$lists_to_print++;
	while ($i < $lists_to_print)
		{
		$row=mysql_fetch_row($rslt);
		$LISTlists[$i] =$row[0];
		$i++;
		}

	$stmt="select status from vicidial_statuses order by status;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$statuses_to_print = mysql_num_rows($rslt);
	$i=0;
		$LISTstatus[$i]='---ALL---';
		$i++;
		$statuses_to_print++;
	while ($i < $statuses_to_print)
		{
		$row=mysql_fetch_row($rslt);
		$LISTstatus[$i] =$row[0];
		$i++;
		}

	$stmt="select distinct status from vicidial_campaign_statuses order by status;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$Cstatuses_to_print = mysql_num_rows($rslt);
	$j=0;
	while ($j < $Cstatuses_to_print)
		{
		$row=mysql_fetch_row($rslt);
		$LISTstatus[$i] =$row[0];
		$i++;
		$j++;
		}
	$statuses_to_print = ($statuses_to_print + $Cstatuses_to_print);

	echo "<HTML><HEAD>\n";

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>ADMINISTRATION: Export Calls Report";

	##### BEGIN Set variables to make header show properly #####
	$ADD =					'100';
	$hh =					'lists';
	$LOGast_admin_access =	'1';
	$SSoutbound_autodial_active = '1';
	$ADMIN =				'admin.php';
	$page_width='770';
	$section_width='750';
	$header_font_size='3';
	$subheader_font_size='2';
	$subcamp_font_size='2';
	$header_selected_bold='<b>';
	$header_nonselected_bold='';
	$lists_color =		'#FFFF99';
	$lists_font =		'BLACK';
	$lists_color =		'#E6E6E6';
	$subcamp_color =	'#C6C6C6';
	##### END Set variables to make header show properly #####

	require("admin_header.php");


	echo "<CENTER><BR>\n";
	echo "<FONT SIZE=3 FACE=\"Arial,Helvetica\"><B>Export Calls Report</B></FONT><BR><BR>\n";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
	echo "<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">";
	echo "<INPUT TYPE=HIDDEN NAME=run_export VALUE=\"1\">";
	echo "<TABLE BORDER=0 CELLSPACING=8><TR><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";

	echo "<font class=\"select_bold\"><B>Date Range:</B></font><BR><CENTER>\n";
	echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'query_date'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php

	echo "<BR>to<BR>\n";
	echo "<INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'end_date'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php

	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=2>\n";
	echo "<font class=\"select_bold\"><B>Campaigns:</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=15 NAME=campaign[] multiple>\n";
		$o=0;
		while ($campaigns_to_print > $o)
		{
			if (ereg("\|$LISTcampaigns[$o]\|",$campaign_string)) 
				{echo "<option selected value=\"$LISTcampaigns[$o]\">$LISTcampaigns[$o]</option>\n";}
			else 
				{echo "<option value=\"$LISTcampaigns[$o]\">$LISTcampaigns[$o]</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";

	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
	echo "<font class=\"select_bold\"><B>Inbound Groups:</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=15 NAME=group[] multiple>\n";
		$o=0;
		while ($groups_to_print > $o)
		{
			if (ereg("\|$LISTgroups[$o]\|",$group_string)) 
				{echo "<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o]</option>\n";}
			else
				{echo "<option value=\"$LISTgroups[$o]\">$LISTgroups[$o]</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";
	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
	echo "<font class=\"select_bold\"><B>Lists:</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=15 NAME=list_id[] multiple>\n";
		$o=0;
		while ($lists_to_print > $o)
		{
			if (ereg("\|$LISTlists[$o]\|",$list_string)) 
				{echo "<option selected value=\"$LISTlists[$o]\">$LISTlists[$o]</option>\n";}
			else 
				{echo "<option value=\"$LISTlists[$o]\">$LISTlists[$o]</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";
	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
	echo "<font class=\"select_bold\"><B>Statuses:</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=15 NAME=status[] multiple>\n";
		$o=0;
		while ($statuses_to_print > $o)
		{
			if (ereg("\|$LISTstatus[$o]\|",$list_string)) 
				{echo "<option selected value=\"$LISTstatus[$o]\">$LISTstatus[$o]</option>\n";}
			else 
				{echo "<option value=\"$LISTstatus[$o]\">$LISTstatus[$o]</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";
	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
	echo "<font class=\"select_bold\"><B>User Groups:</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=15 NAME=user_group[] multiple>\n";
		$o=0;
		while ($user_groups_to_print > $o)
		{
			if (ereg("\|$LISTuser_groups[$o]\|",$user_group_string)) 
				{echo "<option selected value=\"$LISTuser_groups[$o]\">$LISTuser_groups[$o]</option>\n";}
			else 
				{echo "<option value=\"$LISTuser_groups[$o]\">$LISTuser_groups[$o]</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";

	echo "</TD></TR><TR></TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=2>\n";

	echo "</TD></TR><TR></TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=3>\n";
	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE=SUBMIT>\n";
	echo "</TD></TR></TABLE>\n";
	echo "</FORM>\n\n";

	}
exit;

?>