<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: viewpage.php
| Author: PHP-Fusion Development Team
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
require_once "maincore.php";
require_once THEMES."templates/header.php";
require_once INCLUDES."comments_include.php";
require_once INCLUDES."ratings_include.php";
include LOCALE.LOCALESET."custom_pages.php";

$cp_data = array();

if (!isset($_GET['page_id']) || !isnum($_GET['page_id'])) {
    redirect("index.php");
}
$_GET['rowstart'] = isset($_GET['rowstart']) && isnum($_GET['rowstart']) ? $_GET['rowstart'] : 0;

$cp_result = dbquery("SELECT * FROM ".DB_CUSTOM_PAGES."
            WHERE page_id='".intval($_GET['page_id'])."' AND ".groupaccess('page_access')."
            ".(multilang_table("CP") ? "AND ".in_group("page_language", LANGUAGE) : ""));

if (dbrows($cp_result) > 0) {

    $cp_data = dbarray($cp_result);

    $custom_page['title'] = $cp_data['page_title'];
	add_to_title($locale['global_200'].$cp_data['page_title']);
	add_breadcrumb(array('link'=>BASEDIR."viewpage.php?page_id=".$_GET['page_id'], 'title'=>$cp_data['page_title']));
	if ($cp_data['page_keywords'] !=="") { set_meta("keywords", $cp_data['page_keywords']); }
	ob_start();
	if (fusion_get_settings("allow_php_exe")) {
		eval("?>".stripslashes($cp_data['page_content'])."<?php ");
	} else {
		echo "<p>".parse_textarea($cp_data['page_content'])."</p>\n";
	}
	$eval = ob_get_contents();
	ob_end_clean();

    $custom_page['body'] = preg_split("/<!?--\s*pagebreak\s*-->/i", (fusion_get_settings("tinymce_enabled") ? $eval : nl2br($eval)));
    $custom_page['count'] = count($custom_page['body']);

} else {
	add_to_title($locale['global_200'].$locale['401']);
    $custom_page['title'] = $locale['401'];
    $custom_page['error'] = $locale['402'];
}

/**
 * Render Custom Page
 */
opentable($custom_page['title']);
echo "<!--custompages-pre-content-->\n";
if (!empty($custom_page['error'])) {
	echo "<div class='well text-center'>\n";
    echo $custom_page['error'];
	echo "</div>\n";
} else {
    echo $custom_page['body'][$_GET['rowstart']];
}
closetable();

if ($cp_data && $custom_page['count'] > 0) {
    if (isset($_GET['rowstart']) && $_GET['rowstart'] > $custom_page['count']) {
        redirect(BASEDIR."viewpage.php?page_id=".$_GET['page_id']);
    }
    echo "<div class='display-block text-center m-t-5'>\n".makepagenav($_GET['rowstart'], 1, $custom_page['count'], 1,
                                                                       BASEDIR."viewpage.php?page_id=".$_GET['page_id']."&amp;")."\n</div>\n";
}
echo "<!--custompages-after-content-->\n";

if (dbrows($cp_result) && checkgroup($cp_data['page_access'])) {
	if ($cp_data['page_allow_comments']) {
		showcomments("C", DB_CUSTOM_PAGES, "page_id", $_GET['page_id'], BASEDIR."viewpage.php?page_id=".$_GET['page_id']);
	}
	if ($cp_data['page_allow_ratings']) {
		showratings("C", $_GET['page_id'], BASEDIR."viewpage.php?page_id=".$_GET['page_id']);
	}
}

require_once THEMES."templates/footer.php";