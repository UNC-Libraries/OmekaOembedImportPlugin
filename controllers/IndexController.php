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
 * @package OembedImport
 * @author Stephen Ball, Dean Farrell
 * @copyright University of North Carolina at Chapel Hill University Library, 2010
 * @license http://www.apache.org/licenses/LICENSE-2.0.txt
 */

/**
 * OembedImport index controller class
 *
 * @author Stephen Ball, Dean Farrell
 */
class OembedImport_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Display the Oembed Item Import and import verification pages.
     *
     * If data is POSTed: validate and process the given URL for oembed data.
     *
     * Oembed data is displayed for editing and verification before handing
     * off to an import action.
     *
     * @return void
     * @author Stephen Ball, Dean Farrell
     */
    public function indexAction() 
    {
        $oembedImportSession = new Zend_Session_Namespace('OembedImport');
        $oembed_data = null;
        $errors = false;
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $errors = $this->validate_import();
            if (empty($errors)) {
                /*
                    api endpoint validation here so we can easily
                    just determine it once
                */
                $api_endpoint = $this->determine_api_endpoint(trim($_POST['url']));
                if (!$api_endpoint) {
                    $errors['url'] = "Matching API Endpoint not found.";
                }
            }
            if (!empty($errors)) {
                $this->_helper->flashMessenger('Unable to import.', 'error');
            } else {
                $oembed_data = $this->parse_oembed($api_endpoint, trim($_POST['url']));

                if ($oembed_data->type != 'photo') {
                    $this->_helper->flashMessenger('Oembed Item is not a "photo" type. Type: ' . $oembed_data, 'error');
                    $oembed_data = null; // stop further processing
                }
            }
        }

        $this->view->oembed_data = $oembed_data;
        $this->view->errors = $errors;
        $view = $this->view;
    }

    /**
     * The actual item import action.
     *
     * This action is *only* for receiving POSTed data from the index action.
     * A GET request or non "import" POST request is redirected to index.
     *
     * The POST data is parsed into omeka item data structures and imported.
     *
     * @return void
     * @author Stephen Ball, Dean Farrell
     */
    public function importAction()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['import'])) {
            $original_url = $_POST['original_url'];
            $title = "<a href=\"$original_url\">" . trim($_POST['title']) . "</a>";
            $description = trim($_POST['description']);
            $provider_name = trim($_POST['provider_name']);

            $item_metadata = array(
                'collection_id' => $_POST['collection_id'],
                'item_type_id' => $_POST['item_type_id'],
                'tags' => $_POST['tags'],
                'public' => false,
                'featured' => false
            );
            $item_element_texts = array(
                'Dublin Core'=>array(
                    'Title'=>array(array('text'=>$title, 'html'=>true)),
                    'Description'=>array(array('text'=>$description, 'html'=>false)),
                    'Publisher'=>array(array('text'=>$provider_name, 'html'=>false))
                )
            );
            if (!empty($_POST['provider_url'])) {
                $provider_url = $_POST['provider_url'];
                $provider = "<a href=\"$provider_url\">$provider_name</a>";
                $item_element_texts['Dublin Core']['Publisher'] = array(
                    array('text'=>$provider, 'html'=>true)
                );
            }
            $file_metadata = array(
                'file_transfer_type' => 'Url',
                'files' => $_POST['url'],
            );
            $this->view->item_metadata = $item_metadata;
            $this->view->item_element_texts = $item_element_texts;
            $this->view->file_metadata = $file_metadata;
            $item = insert_item($item_metadata, $item_element_texts, $file_metadata);
            if ($item) {
                $this->_helper->flashMessenger('Item imported.', 'success');
            } else {
                $this->_helper->flashMessenger('Import failed.', 'error');
            }
        }
        $this->_helper->redirector('index');
    }

    /**
     * Display the currently stored whitelists.
     *
     * Also handles POSTed requests to update, delete, add whitelists.
     *
     * @return void
     * @author Stephen Ball, Dean Farrell
     */
    public function whitelistsAction()
    {
        $db = get_db();

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (isset($_GET['edit'])) {
                $errors = $this->validate_whitelist($_POST);
                $success_message = 'Whitelist updated.';
                $error_message = 'Update failed.';
                $action = 'edit';
            } elseif (isset($_GET['new'])) {
                $errors = $this->validate_whitelist($_POST);
                $success_message = 'Whitelist added.';
                $error_message = 'Error adding whitelist.';
                $action = 'new';
            } elseif (isset($_GET['delete'])) {
                $success_message = 'Whitelist deleted.';
                $error_message = 'Error deleting whitelist.';
                $action = 'delete';
            }

            if (!empty($errors)) {
                $this->_helper->flashMessenger($error_message, 'error');
            } else {
                // =========================================
                // = validated: submit data or query to db =
                // =========================================
                switch ($action) {
                    case 'edit':
                        if ($this->update_whitelist($_POST, $db)) {
                            $this->_helper->flashMessenger($success_message, 'success');
                            $this->_helper->redirector('whitelists');
                        } else {
                            $this->_helper->flashMessenger($error_message, 'error');
                        }
                        break;
                    case 'new':
                        if ($this->insert_whitelist($_POST, $db)) {
                            $this->_helper->flashMessenger($success_message, 'success');
                            $this->_helper->redirector('whitelists');
                        } else {
                            $this->_helper->flashMessenger($error_message, 'error');
                        }
                        break;
                    case 'delete':
                        if (isset($_POST['cancel'])) {
                            $this->_helper->flashMessenger('Cancelled.', 'success');
                            $this->_helper->redirector('whitelists');
                        }
                        if ($this->delete_whitelist($_POST, $db)) {
                            $this->_helper->flashMessenger($success_message, 'success');
                            $this->_helper->redirector('whitelists');
                        } else {
                            $this->_helper->flashMessenger($error_message, 'error');
                        }
                        break;
                }
            }
        } else {
            $errors = array();
        }
        $sql = "SELECT id, url_scheme, api_endpoint
                FROM `{$db->prefix}oembed_import_whitelists`
                ORDER BY url_scheme";
        $statement = $db->query($sql);
        $whitelist_rows = $statement->fetchAll();
        $this->view->errors = $errors;
        $this->view->whitelist_rows = $whitelist_rows;
    }

    /**
     * Validate the given whitelist data array.
     *
     * Store any errors in an array keyed to the whitelist data array fields.
     *
     * @param array $whitelist
     * @return array $errors
     * @author Stephen Ball, Dean Farrell
     */
    private function validate_whitelist($whitelist)
    {
        $errors = array();
        $url_scheme = trim($whitelist['url_scheme']);
        $api_endpoint = trim($whitelist['api_endpoint']);
        
        if (!strlen($url_scheme)) {
            $errors['url_scheme'] = 'Required.';
        } else {
            if (!preg_match('|^http://|', $url_scheme)) {
                $errors['url_scheme'] = 'Must be an HTTP web address.';
            }
        }
        if (!strlen($api_endpoint)) {
            $errors['api_endpoint'] = 'Required.';
        } else {
            if (!preg_match('|^http://|', $api_endpoint)) {
                $errors['api_endpoint'] = 'Must be an HTTP web address.';
            }
        }
        return $errors;
    }
    
    /**
     * Update the given whitelist in the database.
     *
     * @param array $whitelist whitelist data, fields: id, url_scheme, api_endpoint
     * @param db $db omeka database connection
     * @return boolean $success
     * @author Stephen Ball, Dean Farrell
     */
    private function update_whitelist($whitelist, $db)
    {
        $id = trim($whitelist['id']);
        $url_scheme = trim($whitelist['url_scheme']);
        $api_endpoint = trim($whitelist['api_endpoint']);
        $sql = "UPDATE `{$db->prefix}oembed_import_whitelists` SET url_scheme=?, api_endpoint=? WHERE id=?";
        $success = $db->query($sql, array($url_scheme, $api_endpoint, $id));
        return $success;
    }
    
    /**
     * Insert a new whitelist into the database.
     *
     * @param array $whitelist whitelist data, fields: id, url_scheme, api_endpoint
     * @param db $db omeka database connection
     * @return boolean $success
     * @author Stephen Ball, Dean Farrell
     */
    private function insert_whitelist($whitelist, $db)
    {
        $url_scheme = trim($whitelist['url_scheme']);
        $api_endpoint = trim($whitelist['api_endpoint']);
        $sql = "INSERT INTO `{$db->prefix}oembed_import_whitelists` (url_scheme, api_endpoint) VALUES (?, ?)";
        $success = $db->query($sql, array($url_scheme, $api_endpoint));
        return $success;
    }
    
    /**
     * Delete the given whitelist from the database.
     *
     * @param array $whitelist whitelist data, fields: id, url_scheme, api_endpoint
     * @param db $db omeka database connection
     * @return boolean $success
     * @author Stephen Ball, Dean Farrell
     */
    private function delete_whitelist($whitelist, $db)
    {
        $id = $whitelist['id'];
        $sql = "DELETE FROM `{$db->prefix}oembed_import_whitelists` WHERE id=?";
        $success = $db->query($sql, array($id));
        return $success;
    }
    
    /**
     * Validate POST data from an oembed item import.
     *
     * @param array $_POST
     * @return array $errors
     * @author Stephen Ball, Dean Farrell
     */
    private function validate_import()
    {
        $errors = array();
        // url field cannot be blank
        if (!strlen(trim($_POST['url']))) {
            $errors['url'] = "Required.";
        }
        return $errors;
    }
    
    /**
     * Determine the matching API Endpoint for a given URL.
     *
     * First checks the local whitelist table. If no match,
     * then oohembed's endpoint URL is returned.
     *
     * @param string $url
     * @return string
     * @author Stephen Ball, Dean Farrell
     */
    private function determine_api_endpoint($url)
    {
        $db = get_db();
        $sql = "SELECT id, url_scheme, api_endpoint
                FROM `{$db->prefix}oembed_import_whitelists`";
        $statement = $db->query($sql);
        $whitelist_rows = $statement->fetchAll();
        
        foreach ($whitelist_rows as $row) {
            if ($this->endpoint_match($row['url_scheme'], $url)) {
                return $row['api_endpoint'];
            }
        }
        // there's no match in the stored whitelists, default to oohembed
        // http://oohembed.com/
        return "http://oohembed.com/oohembed/";
    }
    
    /**
     * Determine if the given url matches the given url scheme.
     *
     * @param string $scheme URL scheme, e.g. http://dc.lib.unc.edu/*
     * @param string $url
     * @return boolean
     * @author Stephen Ball, Dean Farrell
     */
    private function endpoint_match($scheme, $url)
    {
        // turn the oembed url scheme into a preg_match pattern
        // e.g. http://dc.lib.unc.edu/*
        // into |^http://dc\.lib\.unc\.edu/.*$|
        $pattern = str_replace('.', '\.', $scheme);
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = "|^$pattern$|";
        return preg_match($pattern, $url);
    }
    
    /**
     * Parse the given oembed api endpoint and URL into a data object.
     *
     * @param string $api_endpoint URL to the matching oembed API
     * @param string $url URL to retrieve data for
     * @return object
     * @author Stephen Ball, Dean Farrell
     */
    private function parse_oembed($api_endpoint, $url)
    {
        $maxwidth = get_option('oembed_import_maxwidth');
        $maxheight = get_option('oembed_import_maxheight');
        if (!$maxwidth) {
            $maxwidth = 1000;
        }
        if (!$maxheight) {
            $maxheight = 1000;
        }
        $oembed_lookup = "$api_endpoint?url=" . urlencode($url);
        $oembed_lookup .= "&maxwidth=$maxwidth&maxheight=$maxheight";

        // try json
        $contents = $this->file_get_contents_curl($oembed_lookup . "&format=json");
        if ($contents) {
            // json_decode doesn't correctly handle escaped quotes
            $contents = str_replace('\"', '&quot;', $contents);
            return json_decode($contents);
        }
        // no json? try xml
        $contents = $this->file_get_contents_curl($oembed_lookup . "&format=xml");
        if ($contents) {
            return new SimpleXMLElement($contents);
        }
    }
    
    private function file_get_contents_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}