<?php

/*
Copyright 2010 The University Library of the University of North Carolina at Chapel Hill

This file is part of the Omeka Oembed Import Plugin.

the Omeka Oembed Import Plugin is free software: you can redistribute it 
and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License,
or (at your option) any later version.

The Omeka Oembed Import Plugin is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.

You should have received a copy of the GNU General Public License
along with the Omeka Oembed Import Plugin. If not, see
<http://www.gnu.org/licenses/gpl-3.0.html>.
*/

/**
 * OembedImport plugin
 *
 * Allow importing of items via oembed-compatible links.
 *
 * @copyright  University of North Carolina at Chapel Hill University Library, 2010
 * @license    http://www.gnu.org/licenses/gpl-3.0.html
 * @version    2.0.0
 * @package OembedImport
 * @author Stephen Ball
 * @author Updated for Omeka 2 by Dean Farrell
 **/

add_plugin_hook('install', 'oembed_import_install');
add_plugin_hook('uninstall', 'oembed_import_uninstall');
add_plugin_hook('define_acl', 'oembed_import_define_acl');
add_plugin_hook('admin_theme_header', 'oembed_import_admin_header');
add_plugin_hook('config_form', 'oembed_import_config_form');
add_plugin_hook('config', 'oembed_import_config');

add_filter('admin_navigation_main', 'oembed_import_admin_navigation');

/**
 * Install OembedImport Plugin
 *
 * 1. Create the whitelist database table.
 * 2. Insert initial whitelists.
 *
 * @return void
 * @author Stephen Ball
 */
function oembed_import_install()
{
    $db = get_db();
    // create oembed whitelist table
    $db->query("CREATE TABLE IF NOT EXISTS `{$db->prefix}oembed_import_whitelists` (
        `id` int(10) unsigned NOT NULL auto_increment,
        `url_scheme` varchar(255) NOT NULL,
        `api_endpoint` varchar(255) NOT NULL,
        PRIMARY KEY(`id`)
    ) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
    
    // initial whitelists
    $db->query("INSERT INTO `{$db->prefix}oembed_import_whitelists`(url_scheme, api_endpoint)
               VALUES ('http://dc.lib.unc.edu/*','http://dc.lib.unc.edu/oembed.php')");
    
    $db->query("INSERT INTO `{$db->prefix}oembed_import_whitelists`(url_scheme, api_endpoint)
               VALUES ('http://*.flickr.com/*','http://www.flickr.com/services/oembed/')");
}

/**
 * Uninstall OembedImport
 *
 * 1. Drop the whitelist database table.
 *
 * @return void
 * @author Stephen Ball
 */
function oembed_import_uninstall()
{
    // drop the tables
    $db = get_db();
    $db->query("DROP TABLE IF EXISTS `{$db->prefix}oembed_import_whitelists`");
}

/**
 * Define ACLs for accessing the OembedImport plugin.
 *
 * @param object $args
 * @return void
 * @author Stephen Ball
 */
function oembed_import_define_acl($args)
{
    $acl = $args['acl'];
    // setup ACL resource so we can restrict oembed to super and admin
    $resource = new Zend_Acl_Resource('OembedImport_Index');
    $acl->addResource($resource);
    $acl->deny(null, 'OembedImport_Index'); // deny ALL
    $acl->allow(array('super', 'admin'), array('OembedImport_Index'));
}

/**
 * Add the "Oembed Import" navigation to the admin interface if
 * the current user has sufficient permissions.
 *
 * @param array $nav
 * @return array $nav
 * @author Stephen Ball
 */
function oembed_import_admin_navigation(array $nav)
{
    if (is_allowed('OembedImport_Index', 'index')) {
        $nav[] = array(
            'label' => __('Oembed Import'),
            'uri' => url('oembed-import'),
            'resource' => 'OembedImport_Index',
            'privilege' => 'add'
        );
    }
    return $nav;
}

/**
 * Add the plugin stylesheet link to plugin admin pages.
 *
 * @param object $request
 * @return void
 * @author Stephen Ball
 */
function oembed_import_admin_header($request)
{
    if ($request->getModuleName() == 'oembed-import') {
        echo '<link rel="stylesheet" href="' . html_escape(css('oembed_import_screen')) . '" />';
    }
}

/**
 * Display the Oembed Import plugin configuration form.
 *
 * @return void
 * @author Stephen Ball
 */
function oembed_import_config_form()
{
    if (!$maxwidth = get_option('oembed_import_maxwidth')) {
        $maxwidth = 1000;
    }
    if (!$maxheight = get_option('oembed_import_maxheight')) {
        $maxheight = 1000;
    }
?>
    <div class="field">
        <label for="oembed_import_maxwidth">Maximum image width</label>
        <?php echo get_view()->formText('oembed_import_maxwidth', $maxwidth, null);?>
        <p class="explanation">Default: 1000</p>
        <p class="explanation">Set to a reasonable pixel size. If too high
            then the oembed provider will likely be slow responding which
            will cause errors during import.</p>
    </div>
    <div class="field">
        <label for="oembed_import_maxheight">Maximum image height</label>
        <?php echo get_view()->formText('oembed_import_maxheight', $maxheight, null);?>
        <p class="explanation">Default: 1000</p>
        <p class="explanation">Set to a reasonable pixel size. If too high
            then the oembed provider will likely be slow responding which
            will cause errors during import.</p>
    </div>
<?php
}

/**
 * Process the Oembed Import configuration POST data.
 *
 * @return void
 * @author Stephen Ball
 */
function oembed_import_config()
{
    if (strlen($_POST['oembed_import_maxwidth'])) {
        if (ctype_digit($_POST['oembed_import_maxwidth'])) {
            set_option('oembed_import_maxwidth', $_POST['oembed_import_maxwidth']);
        }
    }
    if (strlen($_POST['oembed_import_maxwidth'])) {
        if (ctype_digit($_POST['oembed_import_maxwidth'])) {
            set_option('oembed_import_maxheight', $_POST['oembed_import_maxheight']);
        }
    }
}
?>
