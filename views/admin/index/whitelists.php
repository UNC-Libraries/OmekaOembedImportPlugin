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


echo head(array('title' => 'Oembed Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>

<h1>Oembed Import</h1>

<ul id="section-nav" class="navigation">
    <li class="">
        <a href="<?php echo html_escape(url('oembed-import')); ?>">Import</a>
    </li>
    <li class="current">
        <a href="<?php echo html_escape(url('oembed-import/index/whitelists')); ?>">Whitelists</a>
    </li>
</ul>

<?php if (isset($_GET['edit'])): ?>
    <?php
    $id = $_GET['edit'];
    $whitelist = array();
    foreach ($whitelist_rows as $row) {
        if ($row['id'] == $id) {
            $whitelist = $row;
            break;
        }
    }
    display_edit_whitelist($whitelist, $errors);
    ?>
<?php elseif (isset($_GET['delete'])): ?>
    <?php
    $id = $_GET['delete'];
    $whitelist = array();
    foreach ($whitelist_rows as $row) {
        if ($row['id'] == $id) {
            $whitelist = $row;
            break;
        }
    }
    ?>
    <?php display_delete_whitelist($whitelist, $errors); ?>
<?php elseif (isset($_GET['new'])): ?>
    <?php display_new_whitelist($errors); ?>
<?php else: ?>
    <?php display_whitelists_table($whitelist_rows); ?>
<?php endif ?>

<?php 
    foot(); 
?>

<?php
// ========================
// = supporting functions =
// ========================
function display_whitelists_table($whitelist_rows)
{
    ?>
        <div id="primary">
            <h2>Oembed Whitelists</h2>
            <p class="explanation">This plugin has integrated
                <a href="http://code.google.com/p/oohembed/">oohEmbed</a> support
                which will be used if you import a URL without a matching scheme.
                This enables oembed support for
                <a href="http://oohembed.com/#configuration">many popular web services</a> with
                no further configuration necessary.</p>
            <?php echo flash(); ?>
            <table border="0" cellspacing="5" cellpadding="5">
                <thead>
                    <tr>
                        <th>URL Scheme<br />e.g. http://www.flickr.com/photos/*</th>
                        <th>API Endpoint<br />e.g. http://www.flickr.com/services/oembed/</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($whitelist_rows as $row) {
                        $id = $row['id'];
                        ?>
                            <tr>
                                <td>
                                    <?php echo $row['url_scheme']; ?>
                                </td>
                                <td>
                                    <?php echo $row['api_endpoint']; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $id; ?>">Edit</a>,
                                    <a href="?delete=<?php echo $id; ?>">Delete</a>
                                </td>
                            </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td colspan="3"><a href="?new"><button id="add-new">Add New</button></a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php
}

/**
 * Display the "Edit Whitelist" form
 *
 * @param array $whitelist 
 * @param array $errors
 * @return void
 * @author Stephen Ball
 */
function display_edit_whitelist($whitelist, $errors)
{
    $url_scheme = isset($_POST['url_scheme']) ? $_POST['url_scheme'] : $whitelist['url_scheme'];
    $api_endpoint = isset($_POST['api_endpoint']) ? $_POST['api_endpoint'] : $whitelist['api_endpoint'];
    $id = $whitelist['id'];
    ?>
        <div id="primary">
            <h2>Edit Whitelist</h2>
            <?php echo flash(); ?>
            <?php echo display_whitelist_form('Update Whitelist', $errors, $id, $url_scheme, $api_endpoint); ?>
        </div>
    <?php
}

/**
 * Display the "New Whitelist" form
 *
 * @param array $errors
 * @return void
 * @author Stephen Ball
 */
function display_new_whitelist($errors)
{
    $url_scheme = '';
    $api_endpoint = '';
    $id = 'new';
    ?>
        <div id="primary">
            <h2>New Whitelist</h2>
            <?php echo flash(); ?>
            <?php echo display_whitelist_form('Add Whitelist', $errors, $id, $url_scheme, $api_endpoint); ?>
        </div>
    <?php
}

/**
 * Supporting function, display a whitelist editing form.
 *
 * @param string $submit The text for the form's submit button
 * @param string $errors Array of form errors
 * @param string $id whitelist id, "new" if the form is for a new whitelist
 * @param string $url_scheme url_scheme value
 * @param string $api_endpoint api_endpoint value
 * @return void
 * @author Stephen Ball
 */
function display_whitelist_form($submit, $errors, $id, $url_scheme, $api_endpoint)
{
    ?>
        <form action="" method="post" accept-charset="utf-8">
            <input type="hidden" name="id" value="<?php echo $id; ?>" id="id" />
            <table border="0" cellspacing="5" cellpadding="5">
                <thead>
                    <tr>
                        <th>URL Scheme</th>
                        <th>API Endpoint</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td
                        <?php if (isset($errors['url_scheme'])): ?>
                            class="whitelist-error"
                        <?php endif ?>
                        >
                            <input type="text" name="url_scheme" value="<?php echo $url_scheme; ?>" id="url_scheme" size="40" />
                            <?php if (isset($errors['url_scheme'])): ?>
                                <br /><span><?php echo $errors['url_scheme']; ?></span>
                            <?php endif ?>
                        </td>
                        <td
                        <?php if (isset($errors['api_endpoint'])): ?>
                            class="whitelist-error"
                        <?php endif ?>
                        >
                            <input type="text" name="api_endpoint" value="<?php echo $api_endpoint; ?>" id="api_endpoint" size="40" />
                            <?php if (isset($errors['api_endpoint'])): ?>
                                <br /><span><?php echo $errors['api_endpoint']; ?></span>
                            <?php endif ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><input type="submit" value="<?php echo $submit; ?>" /></p>
        </form>
    <?php
}

/**
 * Display the "Delete Whitelist?" form
 *
 * @param array $whitelist
 * @param array $errors
 * @return void
 * @author Stephen Ball
 */
function display_delete_whitelist($whitelist, $errors)
{
    ?>
        <div id="primary">
            <h2>Delete Whitelist?</h2>
            <?php echo flash(); ?>
            <form action="" method="post" accept-charset="utf-8">
                <input type="hidden" name="id" value="<?php echo $whitelist['id']; ?>" />
                <p>Are you sure you want to delete this whitelist?</p>
                <p><?php echo $whitelist['url_scheme']; ?> :  <?php echo $whitelist['api_endpoint']; ?></p>
                <p><input type="submit" name="delete" value="Yes, Delete" /></p>
                <p><input type="submit" name="cancel" value="No, Cancel" /></p>
            </form>
        </div>
    <?php
}
?>
