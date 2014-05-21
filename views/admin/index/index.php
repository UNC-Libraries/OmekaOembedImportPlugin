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
    
    
    head(array('title' => 'Oembed Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>

<h1>Oembed Import</h1>

<ul id="section-nav" class="navigation">
    <li class="current">
        <a href="<?php echo html_escape(uri('oembed-import')); ?>">Import</a>
    </li>
    <li class="">
        <a href="<?php echo html_escape(uri('oembed-import/index/whitelists')); ?>">Whitelists</a>
    </li>
</ul>

<div id="primary">
    <?php if (empty($oembed_data)): ?>
        <h2>Oembed Item Import</h2>
        <?php echo flash(); ?>
        <form id="oembedimport" method="post" action="">
            <?php
            $url = isset($_POST['url']) ? $_POST['url'] : '';
            $collection = isset($_POST['collection']) ? $_POST['collection'] : '';
            $item_type = isset($_POST['item_type']) ? $_POST['item_type'] : '';
            ?>
            <table border="0" cellspacing="5" cellpadding="5">
                <tr>
                    <th>
                        <label for="url"
                        <?php if (isset($errors['url'])): ?>
                            class="whitelist-error"
                        <?php endif ?>
                        >Item URL<?php echo render_error($errors, 'url'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="url" value="<?php echo $url; ?>" id="url" size="60" />
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="collection"
                        <?php if (isset($errors['collection'])): ?>
                            class="whitelist-error"
                        <?php endif ?>
                        >Collection<?php echo render_error($errors, 'collection'); ?></label>
                    </th>
                    <td>
                        <?php
                        $xhtml_properties = array('name'=>'collection', 'id'=>'collection');
                        $value = $collection;
                        echo select_collection($xhtml_properties, $value);
                        ?>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="item_type"
                        <?php if (isset($errors['item_type'])): ?>
                            class="whitelist-error"
                        <?php endif ?>
                        >Item Type<?php echo render_error($errors, 'item_type'); ?></label>
                    </th>
                    <td>
                        <?php
                        $xhtml_properties = array('name'=>'item_type', 'id'=>'item_type');
                        $value = $item_type;
                        echo select_item_type($xhtml_properties, $value);
                        ?>
                    </td>
                </tr>
            </table>
            <input type="submit" value="Import Item" />
        </form>
    <?php else: ?>
        <h2>Import this item?</h2>
        <form action="<?php echo html_escape(uri('oembed-import/index/import')); ?>" method="post" accept-charset="utf-8">
            <table border="0" cellspacing="5" cellpadding="5">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <th>Image Preview</th>
                        <td>
                            <?php if (isset($oembed_data->thumbnail_url)): ?>
                                <img
                                    src="<?php echo $oembed_data->thumbnail_url;?>"
                                    width="<?php echo $oembed_data->thumbnail_width; ?>"
                                    height="<?php echo $oembed_data->thumbnail_height; ?>"
                                    />
                            <?php else: ?>
                                (no thumbnail)
                            <?php endif ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for="title">Title</label>
                        </th>
                        <td>
                            <input type="text" name="title" value="<?php echo $oembed_data->title; ?>" id="title" size="60" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for="description">Description</label>
                        </th>
                        <td>
                            <input type="text" name="description" value="" id="description" size="60" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for="tags">Tags (separated by comma)</label>
                        </th>
                        <td>
                            <input type="text" name="tags" value="" id="oembed-tags" size="60" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="hidden" name="url" value="<?php echo $oembed_data->url; ?>" />
            <input type="hidden" name="collection_id" value="<?php echo $_POST['collection']; ?>" />
            <input type="hidden" name="item_type_id" value="<?php echo $_POST['item_type']; ?>" />
            <input type="hidden" name="provider_name" value="<?php echo $oembed_data->provider_name; ?>" />
            <input type="hidden" name="provider_url" value="<?php echo $oembed_data->provider_url; ?>" />
            <input type="hidden" name="original_url" value="<?php echo $_POST['url']; ?>" />
            <p><input type="submit" name="import" value="Import" /></p>
            <p><input type="submit" class="cancel" name="cancel" value="No, Cancel" /></p>
        </form>
    <?php endif ?>
</div>

<?php
    foot(); 
?>

<?php
/**
 * If $errors contains an error message for $field, display it in error markup.
 *
 * @param array $errors
 * @param string $field
 * @return void
 * @author Stephen Ball, Dean Farrell
 */
function render_error($errors, $field)
{
    if (isset($errors[$field])) {
        ?>
            <strong><?php echo $errors[$field]; ?></strong>
        <?php
    }
}
?>