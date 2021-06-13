<?php
/****************************************************/
/* CoffeeCup Software Form to Mail program          */
/* (C) 2005 CoffeeCup Software                      */
/****************************************************/
/* - Companion Program For Coffeecup Form Builder - */
/* Visit - http://www.coffeecup.com                 */
/****************************************************/
/* Constants   */
/* Version     */ $version = '3.0';
/* Date        */ $date = '1/23/06';
/* Error Level */ error_reporting(E_ALL & ~E_NOTICE);
/* Text File   */ $saveFile = 'c5d4t3i2t1l0e.txt';
   
//////////////////////////////////////////////////////////////
// DISPLAY DEBUGGING INFORMATION AND EXIT
//////////////////////////////////////////////////////////////

$debug = (isset($_REQUEST['debug'])) ? $_REQUEST['debug'] : $debug;

if($debug)
{
  switch($debug)
  {
    case 'info'    :
       phpinfo();
       exit();
    break;

    case 'version'  :
       err("Current MailForm version: <b>".$version."</b><br>Current PHP version: <b>".phpversion()."</b><br> Current Revision Date: <b>$date</b>");
    break;
  }
}

////////////////////////////////////////////////////////////////
//  IF FILEDATA EXISTS ONLY UPLOAD FILES AND EXIT
////////////////////////////////////////////////////////////////

if($_FILES['Filedata'])
{
  //CREATE THE DIRECTORY IF DOESN'T EXISTS (SHOULD HAVE WRITE PERMISSONS)
  if(!is_dir("./files")) mkdir("./files", 0755); 
  
  //MOVE THE UPLOADED FILE
  move_uploaded_file($_FILES['Filedata']['tmp_name'], "./files/".$_FILES['Filedata']['name']);//
  chmod("./files/".$_FILES['Filedata']['name'], 0777);

  exit(); 
}

//////////////////////////////////////////////////////////////
//  SETUP VARS 
//////////////////////////////////////////////////////////////

$date=date("l, F dS, Y \a\\t g:i a");
$server=$_SERVER['SERVER_NAME'];
$emailResponseData="Here is the information submitted to $formName from $_SERVER[REMOTE_ADDR] on $date\r\n\r\n------------------------\r\n";

//COMBINE TO ONE ARRAY
$_REQVARS = array_merge($_POST,$_GET);

$subject        = $_REQVARS['subject'];
$thankyoupage   = $_REQVARS['thankyoupage'];
$xmlFile        = $_REQVARS['xmlfile'];
$unreg          = $_REQVARS['uR'];
$formUserEmail  = $_REQVARS['eM'];

//GET THE DEFAULT EMAIL TO SEND THE FORM TO
$mailto =$_REQVARS['mailto'];
 
//OVERWRITE DEFAULT EMAIL IF ALT EMAIL EXISTS
$mailto = ($_REQVARS['_ALT_EMAIL'] != '') ? $_REQVARS['_ALT_EMAIL'] : $mailto;

//IF NO SUBJECT, MAKE ONE
if(!$subject)
{
  $subject="Form Submission";
}

//////////////////////////////////////////////////////////////
//  GET INFO FROM XML FILE
//////////////////////////////////////////////////////////////

//CONFIG FILE MUST BE IN THE SAME DIRECTORY AS THIS FILE
//AND HAVE THE SAME FIRST PART OF THE NAME. I.E. MYFORM.INC.PHP
/*
I HAVE NO IDEA WHAT THIS DOES
list($formName,$ext) =  split('\.',basename($_SERVER['PHP_SELF']),2);
if (file_exists($formName.".inc.php"))
{
  	include($formName.".inc.php");
}*/

//OPEN AND LOAD THE XML FILE
if (file_exists($xmlFile))
{
    $fd = fopen(basename($xmlFile),'r');
    while(!feof($fd))
    {
        $contents .= fgets($fd,1024);
    }
    fclose($fd);
}
else
{
    err("No &lt;xml&gt; data file found<br>Please upload the data xml file ".$xmlfile);
}

$file_info = preg_replace("/\r|\n/"," ",$contents);

//INCLUDES THE FORM RESULTS IN YOUR THANK YOU PAGE
$incresults =  (preg_match('/<form.*?includeresults="true".*?>/',$file_info));

//IF SHOULD SEND EMAIL OF FORM RESULTS TO THE USER
$emailusr =  (preg_match('/<form.*?emailuser="true".*?>/',$file_info));

preg_match('/<hidden.*?name="thankyoumessage".*?value="(.*?)".*?>/',$file_info,$matches2);
$thanksemailResponseData = unhtmlentities($matches2[1]);

preg_match('/<form.*?bkcolor2="(.*?)".*?>/',$file_info,$matches3);
$backgroundclr = $matches3[1];

preg_match('/<form.*?fontcolor2="(.*?)".*?>/',$file_info,$matches4);
$fontclr = $matches4[1];

preg_match('/<form.*?autoresponse="(.*?)".*?>/',$file_info,$matches5);
$autoresponse = $matches5[1];

//IF THERE IS NO THANK YOU MESSAGE MAKE ONE
if(!$thanksemailResponseData)
{
    $thanksemailResponseData="Thank you for your form submission!";
}

//////////////////////////////////////////////////////////////
// CREATE EMAIL RESPONSE
//////////////////////////////////////////////////////////////

//REVERSING ARRAY ELEMENTS SO THEY APPEAR IN CORRECT FORM ORDER 
$_REQVARS=array_reverse($_REQVARS);

//DELETE VALUES WE WONT NEED IN THE ACTUAL EMAIL
unset($_REQVARS['thankyoupage']);
unset($_REQVARS['subject']);
unset($_REQVARS['mailto']);
unset($_REQVARS['xmlfile']);
unset($_REQVARS['thankyoumessage']);
unset($_REQVARS['uR']);
unset($_REQVARS['eM']);
unset($_REQVARS['_ALT_EMAIL']);

//THERE ARE 3 DIFFERENT RESPONSE TYPES FOR THE FORM
//$formResponseData IS THE HTML RESPONSE THAT IS SENT BACK TO THE USER
//$txtFileData IS THE DATA SUBMITTED THAT IS SAVED TO A TXT FILE
//$emailResponseData IS THE EMAIL SENT TO THE OWNER OF THE FORM

//CREATE HEADERS FOR RESPONSE TYPES
$formResponseData.="<span><p align=\"center\">Below is the information you submitted:</br></br></p><p align=\"center\">";
$txtFileData=$formName.'|'.date("Y-m-d H:i:s").'|'.$_SERVER['REMOTE_ADDR'].'|';

//ADD SUBMITTED FORM DATA TO THE 3 RESPONSES
foreach($_REQVARS as $key=>$value)
{
    $new1=str_replace("_"," ",$key);
   
    $emailResponseData .= "$new1: ".stripslashes($value)."\r\n\r\n";
    $formResponseData.="$new1: ".stripslashes($value)."<br/>";
    $txtFileData .= "$new1: ".stripslashes($value)."|";
}

//FIX UP FORM RESPONSE
$formResponseData.="</p></span>";
$formResponseData=str_replace("_"," ",$formResponseData);
$unregFormResponseData = '';
if($unreg == 'true')
{
	  $unregFormResponseData="<div align=\"center\"><font size=\"1\" face=\"Arial\">Created with CoffeeCup Form Builder <a href=\"http://www.coffeecup.com/\" target=\"_blank\" title=\"CoffeeCup Form Builder\">Download It Here</a></font></div>";
}

//FIX UP EMAIL RESPONSE
if($unreg == 'true')
{
  $emailResponseData .= "------------------------\r\n\r\nThis Form was sent to you using CoffeeCup Form Builder.\r\nPlease tell a friend about us: http://www.coffeecup.com/form-builder/\r\n";
}

$emailResponseData .= $autoresponse;

//////////////////////////////////////////////////////////////
// EMAIL RESPONSES
//////////////////////////////////////////////////////////////

//CONSTRUCT A PROPER MIME/UTF-8 FOR EXTENDED ASCII, AND INTERNATIONALIZATION
$headers = "MIME-Version: 1.0\r\n" . "Content-type: text/plain; charset=UTF-8\r\n\r\n";

//IF THEY SPECIFY "EMAIL" IN THEIR FORM, IT WILL SET THE REPLY-TO FIELD.
if($formUserEmail)
{
	//SEND EMAIL TO FORMS OWNER
  $sentMail1 = mail($mailto,$subject,$emailResponseData,"Reply-To: $formUserEmail\r\nFrom: $formUserEmail\r\n$headers");
  if (!$sentMail1) err("Cannot send email at #1!");
}
else
{
	//SEND EMAIL AS REGULAR WEB SERVER USER
	$sentMail2 = mail($mailto,$subject,$emailResponseData,$headers);
	if (!$sentMail2) err("Cannot send email at #2!"."<br>".$mailto);
}

//MAIL TO FORM USER
if($emailusr)
{
 	if($formUserEmail)
  {
  	$sentMail3 = mail($formUserEmail,$subject,$emailResponseData,"Reply-To: $mailto\r\nFrom: $mailto\r\n$headers");
  	if (!$sentMail3) err("Cannot send email at #3!");
  }
}

//////////////////////////////////////////////////////////////
//  SAVE TO TXT FILE
//////////////////////////////////////////////////////////////

if ($saveFile != '[FILENAME]')
{
	$fd = fopen($saveFile,"a+");
	ccfputcsv($fd, $txtFileData);
	fclose($fd);
}

//////////////////////////////////////////////////////////////
//  CREATE AND SHOW THE HTML RESPONSE PAGE
//////////////////////////////////////////////////////////////
 
//IF NOT SUPPOSED TO INCLUDE FORM RESULTS DELETE RESULTS
if(!$incresults)
{
   $formResponseData="";
}

//GO TO THANK YOU PAGE IF NECESSARY
if($thankyoupage)
{
  header("Location: $thankyoupage");
}
else
{
         print <<<__EOT__
<html>

<head>
 <title>Form Submitted</title>
 <style type="text/css">
 <!--
 body {
    background-color:$backgroundclr;
 }
 #message {
        width:720px;
    margin:9px auto;
        text-align:center;
    font:bold 14px 'Trebuchet MS',arial,helvetica,sans-serif;
    color:$fontclr;
 }
#message span {
    font-weight:normal;
}
//-->
 </style>
</head>

<body bgcolor="$background-color">
<center>
 <div id="message"><div>$thanksemailResponseData</div>
<br /><br />
$formResponseData
<br /><br />
$unregFormResponseData
</center>
</body>

</html>
__EOT__;

}

function err($string)
{
        global $version;
        echo("<h2 style=\"font:normal 20px 'Trebuchet MS',arial\">$string</h2>");
        echo("<h2 style=\"font:normal 12px 'Trebuchet MX',arial\">There was an error running the Form Builder script.<br /><!-- $version --></h2>");
        exit();
}

function ccfputcsv($handle, $myemailResponseData)
{
   fputs($handle, $myemailResponseData."\n");//stripslashes(substr($str, 0, -1))

   return strlen($myemailResponseData);
}

function unhtmlentities($string)
{
   $trans_tbl = get_html_translation_table(HTML_ENTITIES);
   $trans_tbl = array_flip($trans_tbl);
   return strtr($string, $trans_tbl);
}



