<?php
// check if logged in
session_start();
if (!isset($_SESSION['myusername'])) {
    header("location:Login.php");
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 14">
<link rel=File-List href="UseCasesSafeGamePlay_files/filelist.xml">
<style id="UseCasesSafeGamePlay_28797_Styles"><!--table
	{mso-displayed-decimal-separator:"\.";
	mso-displayed-thousand-separator:"\,";}
.xl1528797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl6328797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:.5pt solid gray;
	background:#FDE9D9;
	mso-pattern:black none;
	white-space:normal;}
.xl6428797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl6528797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl6628797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl6728797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl6828797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl6928797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:"\@";
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt solid gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl7028797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:"\@";
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt solid gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl7128797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:"\@";
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt solid gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl7228797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl7328797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:700;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:left;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:none;
	border-bottom:.5pt solid gray;
	border-left:.5pt solid gray;
	background:#EEECE1;
	mso-pattern:black none;
	white-space:normal;}
.xl7428797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:700;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:left;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:none;
	border-bottom:.5pt solid gray;
	border-left:none;
	background:#EEECE1;
	mso-pattern:black none;
	white-space:normal;}
.xl7528797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:700;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:left;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt solid gray;
	border-left:none;
	background:#EEECE1;
	mso-pattern:black none;
	white-space:normal;}
.xl7628797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:"\@";
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt solid gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl7728797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl7828797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl7928797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:"\@";
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt solid gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl8028797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl8128797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl8228797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:"\@";
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt solid gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl8328797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt dashed gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl8428797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:black;
	font-size:11.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:nowrap;}
.xl8528797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:red;
	font-size:11.0pt;
	font-weight:400;
	font-style:italic;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl8628797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:red;
	font-size:11.0pt;
	font-weight:400;
	font-style:italic;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt dashed gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt solid gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
.xl8728797
	{padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:red;
	font-size:11.0pt;
	font-weight:400;
	font-style:italic;
	text-decoration:none;
	font-family:Calibri, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border-top:.5pt solid gray;
	border-right:.5pt solid gray;
	border-bottom:.5pt dashed gray;
	border-left:.5pt dashed gray;
	mso-background-source:auto;
	mso-pattern:auto;
	white-space:normal;}
--></style>
<title>Uses Cases for Safe Game Play</title>
</head>

<body>
<!--[if !excel]>&nbsp;&nbsp;<![endif]-->
<!--The following information was generated by Microsoft Excel's Publish as Web
Page wizard.-->
<!--If the same item is republished from Excel, all information between the DIV
tags will be replaced.-->
<!----------------------------->
<!--START OF OUTPUT FROM EXCEL PUBLISH AS WEB PAGE WIZARD -->
<!----------------------------->

<div id="UseCasesSafeGamePlay_28797" align=center x:publishsource="Excel">

<h1 style='color:black;font-family:Calibri;font-size:14.0pt;font-weight:800;
font-style:normal'>Uses Cases for Safe Game Play</h1>

<table border=0 cellpadding=0 cellspacing=0 width=1230 style='border-collapse:
 collapse;table-layout:fixed;width:922pt'>
 <col width=64 style='width:48pt'>
 <col width=311 style='mso-width-source:userset;mso-width-alt:11373;width:233pt'>
 <col width=64 style='width:48pt'>
 <col width=456 style='mso-width-source:userset;mso-width-alt:16676;width:342pt'>
 <col width=335 style='mso-width-source:userset;mso-width-alt:12251;width:251pt'>
 <tr height=43 style='mso-height-source:userset;height:32.25pt'>
  <td height=43 class=xl6328797 width=64 style='height:32.25pt;width:48pt'>Use
  Case Number</td>
  <td class=xl6328797 width=311 style='border-left:none;width:233pt'>Use Case
  Name</td>
  <td class=xl6328797 width=64 style='border-left:none;width:48pt'>Status</td>
  <td class=xl6328797 width=456 style='border-left:none;width:342pt'>Descripton</td>
  <td class=xl6328797 width=335 style='border-left:none;width:251pt'>Details</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td colspan=5 height=20 class=xl7328797 width=1230 style='border-right:.5pt solid gray;
  height:15.0pt;width:922pt'>Game Play</td>
 </tr>
 <tr height=160 style='height:120.0pt'>
  <td height=160 class=xl6928797 width=64 style='height:120.0pt;border-top:
  none;width:48pt'>1.1</td>
  <td class=xl6428797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game started on table with two players</td>
  <td class=xl6428797 width=64 style='border-top:none;border-left:none;
  width:48pt'>Verified</td>
  <td class=xl6428797 width=456 style='border-top:none;border-left:none;
  width:342pt'>One of two seated players press the start game. Board game for
  all players at table is updated with initial setup. Non-occupied seats shown
  as Empty seats and ignored during play.</td>
  <td class=xl6528797 width=335 style='border-top:none;border-left:none;
  width:251pt'>1) Player who first joined is dealer<br>
    2) Other places small blind<br>
    3) Dealer places big blind<br>
    4) Other player has first turn, check not allowed<br>
    5) Board game for all players updated with game info<br>
    6) Each player's board game updated with cards, which are bigger than other
  player's card</td>
 </tr>
 <tr height=140 style='height:105.0pt'>
  <td height=140 class=xl7028797 width=64 style='height:105.0pt;border-top:
  none;width:48pt'>1.2</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game started on table with three players</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>Verified</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>One of three seated players press the start game. Board game for
  all players at table is updated with initial setup. Non-occupied seats shown
  as Empty seats and ignored during play.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>1) Player who first joined is dealer<br>
    2) First player to left of dealer places small blind<br>
    3) Second player to left of dealer places big blind<br>
    4) Dealer has turn<br>
    5) Board game for all players updated with game info<br>
    6) Each player's board game updated with hands</td>
 </tr>
 <tr height=140 style='height:105.0pt'>
  <td height=140 class=xl7028797 width=64 style='height:105.0pt;border-top:
  none;width:48pt'>1.3</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game started on table with full table (four or more players)</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>Verified</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>One of four or more seated players press the start game. Board
  game for all players at table is updated with initial setup.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>1) Player who first joined is dealer<br>
    2) First player to left of dealer places small blind<br>
    3) Second player to left of dealer places big blind<br>
    4) Third player to left of dealer has turn<br>
    5) Board game for all players updated with game info<br>
    6) Each player's board game updated with hands</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7028797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>1.4</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game restarted after end</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>Verified</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>New game identifies next dealer to be the active dealer after
  current and blind bets and first player per rules in use cases 1.1 to 1.3</td>
  <td class=xl8528797 width=335 style='border-top:none;border-left:none;
  width:251pt'>TODO: should not be manual but automatic.</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7128797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>1.5</td>
  <td class=xl6828797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game restarted before it ends</td>
  <td class=xl6828797 width=64 style='border-top:none;border-left:none;
  width:48pt'>Verified</td>
  <td class=xl6828797 width=456 style='border-top:none;border-left:none;
  width:342pt'>One of seated players presses game start while game is in
  progress</td>
  <td class=xl8628797 width=335 style='border-top:none;border-left:none;
  width:251pt'>TODO: should not allow game start while in progress.</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td colspan=5 height=20 class=xl7328797 width=1230 style='border-right:.5pt solid gray;
  height:15.0pt;width:922pt'>Time Out</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl6928797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>2.1</td>
  <td class=xl6428797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Player with turn does not make a move before time out.</td>
  <td class=xl6428797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6428797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Next player is identified, and raise amount calculated based on
  previous two plays. See Moves use cases for check rules.</td>
  <td class=xl6528797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=60 style='height:45.0pt'>
  <td height=60 class=xl7128797 width=64 style='height:45.0pt;border-top:none;
  width:48pt'>2.2</td>
  <td class=xl6828797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Player times out turn for the third time</td>
  <td class=xl6828797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6828797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Player is ejected, stake forfeit, and eveyone's board games are
  updated to show player was ejected. Player is ejected whether time out is on
  consecutive turns or otherwise.</td>
  <td class=xl7228797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td colspan=5 height=20 class=xl7328797 width=1230 style='border-right:.5pt solid gray;
  height:15.0pt;width:922pt'>Moves</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl6928797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>3.1</td>
  <td class=xl6428797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Player makes move (applies to all uses cases in section)</td>
  <td class=xl6428797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6428797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Upon player's move, update his stake and everyone's game board
  with stake and move. Identify next player and place timer.</td>
  <td class=xl8728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>TODO: show clock with remaining time for play</td>
 </tr>
 <tr height=41 style='mso-height-source:userset;height:30.75pt'>
  <td height=41 class=xl7028797 width=64 style='height:30.75pt;border-top:none;
  width:48pt'>3.2</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Player at table with three or more players folds</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Cards for player removed from everyone's game board and status
  changed to LEFT. Player no longer gets a turn.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7028797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>3.3</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Turn given to player with big blind on first round</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Check button allowed on first round to big blind player only.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7028797 width=64 style='height:15.0pt;border-top:none;
  width:48pt'>3.4</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Player folds with only two players</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Remaining player becomes winner, see 2.6</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7028797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>3.5</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Round one ends</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>First three community cards dealt by dealer when round one ends.
  Check button enabled from now on.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7028797 width=64 style='height:15.0pt;border-top:none;
  width:48pt'>3.6</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Round two ends</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Turn card dealt by dealer when round two ends.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7028797 width=64 style='height:15.0pt;border-top:none;
  width:48pt'>3.7</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Round three ends</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>River card dealt by dealer when round three ends.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=60 style='height:45.0pt'>
  <td height=60 class=xl7028797 width=64 style='height:45.0pt;border-top:none;
  width:48pt'>3.8</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Round four ends</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>End game: identify all player hands, highest ranking hand and
  winner. Update stakes for winner.</td>
  <td class=xl8528797 width=335 style='border-top:none;border-left:none;
  width:251pt'>TODO: next game should automatically start<br>
    TODO: for folded players, allow choice to show cards, no show by default</td>
 </tr>
 <tr height=20 style='page-break-before:always;height:15.0pt'>
  <td colspan=5 height=20 class=xl7328797 width=1230 style='border-right:.5pt solid gray;
  height:15.0pt;width:922pt'>Card dealing</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7028797 width=64 style='height:30.0pt;width:48pt'>4.1</td>
  <td class=xl6628797 width=311 style='border-left:none;width:233pt'>Game
  started with dealer on seat 0</td>
  <td class=xl6628797 width=64 style='border-left:none;width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-left:none;width:342pt'>Animation
  for player cards and community cards being dealt start on dealer button at
  seat 0.</td>
  <td class=xl6728797 width=335 style='border-left:none;width:251pt'>&nbsp;</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7028797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>4.2</td>
  <td class=xl6628797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game started with dealer on seat 1</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Animation for player cards and community cards being dealt start
  on dealer button at seat 1.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7028797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>4.3</td>
  <td class=xl6828797 width=311 style='border-top:none;border-left:none;
  width:233pt'>Game started with dealer on seat 2</td>
  <td class=xl6628797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Animation for player cards and community cards being dealt start
  on dealer button at seat 2.</td>
  <td class=xl6728797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=40 style='height:30.0pt'>
  <td height=40 class=xl7128797 width=64 style='height:30.0pt;border-top:none;
  width:48pt'>4.4</td>
  <td class=xl6628797 width=311 style='border-left:none;width:233pt'>Game
  started with dealer on seat 4</td>
  <td class=xl6828797 width=64 style='border-top:none;border-left:none;
  width:48pt'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Animation for player cards and community cards being dealt start
  on dealer button at seat 3.</td>
  <td class=xl7228797 width=335 style='border-top:none;border-left:none;
  width:251pt'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td colspan=5 height=20 class=xl7328797 width=1230 style='border-right:.5pt solid gray;
  height:15.0pt;width:922pt'>Player leaves</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7628797 style='height:15.0pt;border-top:none'>5.1</td>
  <td class=xl7728797 style='border-top:none;border-left:none'>Player in seat 0
  leaves</td>
  <td class=xl7728797 style='border-top:none;border-left:none'>&nbsp;</td>
  <td class=xl6428797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Player status is updated to left and turn for seat 0 is skipped.</td>
  <td class=xl7828797 style='border-top:none;border-left:none'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7928797 style='height:15.0pt;border-top:none'>5.2</td>
  <td class=xl8028797 style='border-top:none;border-left:none'>Player in seat 1
  leaves</td>
  <td class=xl8028797 style='border-top:none;border-left:none'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Player status is updated to left and turn for seat 0 is skipped.</td>
  <td class=xl8128797 style='border-top:none;border-left:none'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7928797 style='height:15.0pt;border-top:none'>5.3</td>
  <td class=xl8028797 style='border-top:none;border-left:none'>Player in seat 2
  leaves</td>
  <td class=xl8028797 style='border-top:none;border-left:none'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Player status is updated to left and turn for seat 0 is skipped.</td>
  <td class=xl8128797 style='border-top:none;border-left:none'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl7928797 style='height:15.0pt;border-top:none'>5.4</td>
  <td class=xl8028797 style='border-top:none;border-left:none'>Player in seat 3
  leaves</td>
  <td class=xl8028797 style='border-top:none;border-left:none'>&nbsp;</td>
  <td class=xl6628797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Player status is updated to left and turn for seat 0 is skipped.</td>
  <td class=xl8128797 style='border-top:none;border-left:none'>&nbsp;</td>
 </tr>
 <tr height=20 style='height:15.0pt'>
  <td height=20 class=xl8228797 style='height:15.0pt;border-top:none'>5.5</td>
  <td class=xl8328797 style='border-top:none;border-left:none'>Player whose
  turn it is to play leaves</td>
  <td class=xl8328797 style='border-top:none;border-left:none'>&nbsp;</td>
  <td class=xl6828797 width=456 style='border-top:none;border-left:none;
  width:342pt'>Player status is updated to left and next player identified.</td>
  <td class=xl8428797 style='border-top:none;border-left:none'>&nbsp;</td>
 </tr>
 <![if supportMisalignedColumns]>
 <tr height=0 style='display:none'>
  <td width=64 style='width:48pt'></td>
  <td width=311 style='width:233pt'></td>
  <td width=64 style='width:48pt'></td>
  <td width=456 style='width:342pt'></td>
  <td width=335 style='width:251pt'></td>
 </tr>
 <![endif]>
</table>

</div>


<!----------------------------->
<!--END OF OUTPUT FROM EXCEL PUBLISH AS WEB PAGE WIZARD-->
<!----------------------------->
</body>

</html>



