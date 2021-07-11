<?php
/* Copyright (C) 2017-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOIPCHECK'))      define('NOIPCHECK','1');				// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}				    // If this page is public (can be called outside logged session)
if (! defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');
}				// Do not check IP defined into conf $dolibarr_main_restrict_ip
if (! defined("MAIN_LANG_DEFAULT") && empty($_GET['lang'])) {
	define('MAIN_LANG_DEFAULT', 'auto');
}
if (! defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}


//define('SYSLOG_FILE_ADDIP', 1);
define('SYSLOG_FILE_ADDSUFFIX', 'register');


// Add specific definition to allow a dedicated session management
include './mainmyaccount.inc.php';

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) {
	$i--;
	$j--;
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) {
	$res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
}
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) {
	$res=include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) {
	$res=@include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php")) {
	$res=@include "../../../main.inc.php";
}
if (! $res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
dol_include_once('/sellyoursaas/class/packages.class.php');
dol_include_once('/sellyoursaas/class/deploymentserver.class.php');

//$langs=new Translate('', $conf);
//$langs->setDefaultLang(GETPOST('lang', 'aZ09')?GETPOST('lang', 'aZ09'):'auto');
$langs->loadLangs(array("main","companies","sellyoursaas@sellyoursaas","errors"));

if ($langs->defaultlang == 'en_US') {
    $langsen = $langs;
} else {
    $langsen=new Translate('', $conf);
    $langsen->setDefaultLang('en_US');
    $langsen->loadLangs(array("main","companies","sellyoursaas@sellyoursaas","errors"));
}


$reusesocid = GETPOST('reusesocid', 'int');
$codevalid = GETPOST('codevalid');
$productref = GETPOST('productref', 'alpha');
$sldAndSubdomain = GETPOST('sldAndSubdomain', 'alpha');

$extcss=GETPOST('extcss', 'alpha');
if (empty($extcss)) {
    $extcss = getDolGlobalString('SELLYOURSAAS_EXTCSS', 'dist/css/myaccount.css');
} elseif ($extcss == 'generic') {
    $extcss = 'dist/css/myaccount.css';
}


// SERVER_NAME here is myaccount.mydomain.com (we can exploit only the part mydomain.com)
include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
$domainname = getDomainFromURL($_SERVER["SERVER_NAME"], 1);

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$favicon=getDomainFromURL($_SERVER['SERVER_NAME'], 0);
if (! preg_match('/\.(png|jpg)$/', $favicon)) {
    $favicon.='.png';
}
if (getDolGlobalString('MAIN_FAVICON_URL')) {
    $favicon=getDolGlobalString('MAIN_FAVICON_URL');
}

$head = '';
if ($favicon) {
    $href = 'img/'.$favicon;
    if (preg_match('/^http/i', $favicon)) {
        $href = $favicon;
    }
    $head.='<link rel="icon" href="'.$href.'">'."\n";
}
$head .= '<!-- Bootstrap core CSS -->';
$head .= '<link href="dist/css/bootstrap.css" type="text/css" rel="stylesheet">';
$head .= '<link href="'.$extcss.'" type="text/css" rel="stylesheet">';

if (getDolGlobalString('SELLYOURSAAS_GOOGLE_RECAPTCHA_ON')) {
    $head .= '<script src="https://www.google.com/recaptcha/api.js"></script>';
}

// Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second
if ((float) DOL_VERSION <= 19) {
    $arrayofjs=array(
        '/includes/jstz/jstz.min.js'.(empty($conf->dol_use_jmobile)?'':'?version='.urlencode(DOL_VERSION)),
        '/core/js/dst.js'.(empty($conf->dol_use_jmobile) ? '' : '?version='.urlencode(DOL_VERSION))
    );
} else {
    $arrayofjs=array(
        '/core/js/dst.js'.(empty($conf->dol_use_jmobile) ? '' : '?version='.urlencode(DOL_VERSION))
    );
}

$title = $langs->trans("Registration").($tmpproduct->label ? ' ('.$tmpproduct->label.')' : '');

$prefix=dol_getprefix('');
$cookieregistrationa='DOLREGISTERA_'.$prefix;
if (empty($_COOKIE[$cookieregistrationa])) {
    setcookie($cookieregistrationa, 1, 0, "/", null, false, true);	// Cookie to count nb of registration from this computer
}

llxHeader($head, $title, '', '', 0, 0, $arrayofjs, array(), '', 'register', '', 0, 1);

?>
<div id="waitMask" style="display:none;">
	<font size="3em" style="color:#888; font-weight: bold;"><?php echo $langs->trans("InstallingInstance") ?><br><?php echo $langs->trans("PleaseWait") ?><br></font>
	<img id="waitMaskImg" width="100px" src="<?php echo 'ajax-loader.gif'; ?>" alt="Loading" />
</div>

<form action="register_instance.php" name="formregister" method="post" id="formregister">
    <input type="hidden" name="reusesocid" value="<?php echo dol_escape_htmltag($reusesocid); ?>" />
    <input type="hidden" name="codevalid" value="<?php echo dol_escape_htmltag($codevalid); ?>" />
    <input type="hidden" name="productref" value="<?php echo dol_escape_htmltag($productref); ?>" />
    <input type="hidden" name="sldAndSubdomain" value="<?php echo dol_escape_htmltag($sldAndSubdomain); ?>" />
</form>

<script type="text/javascript" language="javascript">
	jQuery(document).ready(function() {
		/* Sow hourglass */
		$('#formregister').submit(function() {
				console.log("We clicked on submit on register.php")

				jQuery(document.body).css({ 'cursor': 'wait' });
				jQuery("div#waitMask").show();
				jQuery("#waitMask").css("opacity"); // must read it first
				jQuery("#waitMask").css("opacity", "0.6");

				return true;	/* Use return false to show the hourglass without submitting the page (for debug) */
		});
        $("#formregister").submit();
	});
</script>


<?php

llxFooter('', 'public', 1);		// We disabled output of messages. Already done into page
$db->close();
