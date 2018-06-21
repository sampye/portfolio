<?php

/*
 * This is a small PHP-script that I wrote for a client to upload a XML-file
 * and to process and add the data within to an OpenCart DB schema. There's a small
 * XML-file sample accompanied to give you an idea about what this script is processing.
 * It's hardly flawless and didn't have any comments prior to this, so this is also kind
 * of retrospective view of my own work.
 *
 * It's also anonymized and no longer in use anywhere.
 *
 */

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('max_execution_time', 10000);
ini_set('max_input_time', -1);
set_time_limit(0);

//include('/var/www/htdocs/admin/config.php');
include('/usr/share/nginx/html/admin/config.php');

//$directory = '/var/www/htdocs/admin/input/';
$directory = '/usr/share/nginx/html/admin/input/';

/*
 *  I tried to get myself access to the XML providers API to download the file directly, but to no avail.
 *  They only provided access to those who had a deal with them. So I had to resort to the next best thing.
*/

if (isset($_POST['submit'])) {
    if ($_FILES['xmlupload']['error'] > 0) {
        echo "Error: " . $_FILES["xmlupload"]["error"] . "<br />";
    } else {
        move_uploaded_file($_FILES['xmlupload']['tmp_name'], $directory . $_FILES['xmlupload']['name']);
    }
}

$scanned_directory = array_diff(scandir($directory), array('..', '.'));
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

/*
 *  For these insert/add-methods I can not take credit for. These are OpenCart's own DB-methods that
 *  I just re-purposed for my own use. Looking back now I don't know why I didn't simply call f.e.
 *  Category-class model to achieve the same. $data parameter is an array containing parsing data.
 */

function insertCategory($data, $mysqli)
{
    $mysqli->query("INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");

    $category_id = $mysqli->insert_id;

    if (isset($data['image'])) {
        $mysqli->query("UPDATE " . DB_PREFIX . "category SET image = '" . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8') . "' WHERE category_id = '" . (int)$category_id . "'");
    }

    foreach ($data['category_description'] as $language_id => $value) {
        $mysqli->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', name = '" . $mysqli->real_escape_string($value['name']) . "', meta_keyword = '" . $value['meta_keyword'] . "', meta_description = '" . $value['meta_description'] . "', description = '" . $value['description'] . "'");
    }

    // MySQL Hierarchical Data Closure Table Pattern
    $level = 0;

    $query = $mysqli->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");

    while ($result = $query->fetch_assoc()) {
        $mysqli->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");

        $level++;

    }
    $mysqli->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

    if (isset($data['category_filter'])) {
        foreach ($data['category_filter'] as $filter_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
        }
    }

    if (isset($data['category_store'])) {
        foreach ($data['category_store'] as $store_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
        }
    }

    // Set which layout to use with this category
    if (isset($data['category_layout'])) {
        foreach ($data['category_layout'] as $store_id => $layout) {
            if ($layout['layout_id']) {
                $mysqli->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout['layout_id'] . "'");
            }
        }
    }

    if ($data['keyword']) {
        $mysqli->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $data['keyword'] . "'");
    }
}

function insertManufacturer($data, $mysqli)
{
    $mysqli->query("INSERT INTO " . DB_PREFIX . "manufacturer SET name = '" . $data['name'] . "', sort_order = '" . (int)$data['sort_order'] . "'");

    $manufacturer_id = $mysqli->insert_id;

    if (isset($data['image'])) {
        $mysqli->query("UPDATE " . DB_PREFIX . "manufacturer SET image = '" . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8') . "' WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
    }

    if (isset($data['manufacturer_store'])) {
        foreach ($data['manufacturer_store'] as $store_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$store_id . "'");
        }
    }

    if ($data['keyword']) {
        $mysqli->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$manufacturer_id . "', keyword = '" . $data['keyword'] . "'");
    }

}

function insertOptionValue($data, $mysqli)
{
    $option_id = $data['option_id'];
    $option_value = $data['option_value'];
    if (isset($data['option_value'])) {
        $mysqli->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8') . "', sort_order = '" . (int)$option_value['sort_order'] . "'");

        $option_value_id = $mysqli->insert_id;

        $mysqli->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . 1 . "', option_id = '" . (int)$option_id . "', name = '" . $option_value['option_value_description'][0] . "'");
        $mysqli->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . 2 . "', option_id = '" . (int)$option_id . "', name = '" . $option_value['option_value_description'][0] . "'");

    }
}

function addProduct($data, $mysqli)
{

    $mysqli->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $data['model'] . "', sku = '" . $data['sku'] . "', upc = '" . $data['upc'] . "', ean = '" . $data['ean'] . "', jan = '" . $data['jan'] . "', isbn = '" . $data['isbn'] . "', mpn = '" . $data['mpn'] . "', location = '" . $data['location'] . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $data['date_available'] . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . $data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW()");
    $product_id = $mysqli->insert_id;

    if (isset($data['image'])) {
        $mysqli->query("UPDATE " . DB_PREFIX . "product SET image = '" . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8') . "' WHERE product_id = '" . (int)$product_id . "'");
    }

    foreach ($data['product_description'] as $language_id => $value) {
        $mysqli->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $mysqli->real_escape_string($value['name']) . "', meta_keyword = '" . $value['meta_keyword'] . "', meta_description = '" . $value['meta_description'] . "', description = '" . $mysqli->real_escape_string($value['description']) . "', tag = '" . $value['tag'] . "'");
    }

    if (isset($data['product_store'])) {
        foreach ($data['product_store'] as $store_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
        }
    }

    if (isset($data['product_attribute'])) {
        foreach ($data['product_attribute'] as $product_attribute) {
            if ($product_attribute['attribute_id']) {
                $mysqli->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                    $mysqli->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" . $product_attribute_description['text'] . "'");
                }
            }
        }
    }

    if (isset($data['product_option'])) {
        foreach ($data['product_option'] as $product_option) {
            if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {

                $mysqli->query("INSERT INTO " . DB_PREFIX . "product_option SET  product_id = '" . (int)$product_id . "', option_value = '" . null . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                $product_option_id = $mysqli->insert_id;

                if (isset($product_option['product_option_value']) && count($product_option['product_option_value']) > 0) {
                    foreach ($product_option['product_option_value'] as $product_option_value) {
                        $mysqli->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $product_option_value['price_prefix'] . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $product_option_value['points_prefix'] . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $product_option_value['weight_prefix'] . "', sku = '" . $product_option_value['sku'] . "'");
                    }
                } else {
                    $mysqli->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_option_id = '" . $product_option_id . "'");
                }
            } else {
                $mysqli->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value = '" . $product_option['option_value'] . "', required = '" . (int)$product_option['required'] . "'");
            }
        }
    }

    if (isset($data['product_discount'])) {
        foreach ($data['product_discount'] as $product_discount) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $product_discount['date_start'] . "', date_end = '" . $product_discount['date_end'] . "'");
        }
    }

    if (isset($data['product_special'])) {
        foreach ($data['product_special'] as $product_special) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $product_special['date_start'] . "', date_end = '" . $product_special['date_end'] . "'");
        }
    }

    if (isset($data['product_image'])) {
        foreach ($data['product_image'] as $product_image) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8') . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
        }
    }

    if (isset($data['product_download'])) {
        foreach ($data['product_download'] as $download_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
        }
    }

    if (isset($data['product_category'])) {
        foreach ($data['product_category'] as $category_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
        }
    }

    if (isset($data['product_filter'])) {
        foreach ($data['product_filter'] as $filter_id) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
        }
    }

    if (isset($data['product_related'])) {
        foreach ($data['product_related'] as $related_id) {
            $mysqli->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
            $mysqli->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
        }
    }

    if (isset($data['product_reward'])) {
        foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
        }
    }

    if (isset($data['product_layout'])) {
        foreach ($data['product_layout'] as $store_id => $layout) {
            if ($layout['layout_id']) {
                $mysqli->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout['layout_id'] . "'");
            }
        }
    }

    if ($data['keyword']) {
        $mysqli->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $mysqli->escape($data['keyword']) . "'");
    }

    if (isset($data['product_profiles'])) {
        foreach ($data['product_profiles'] as $profile) {
            $mysqli->query("INSERT INTO `" . DB_PREFIX . "product_profile` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$profile['customer_group_id'] . ", `profile_id` = " . (int)$profile['profile_id']);
        }
    }
}

/*
 * These however are my own work, these are for maintaining the database when new data is being imported.
 */

function updateProduct($product, $mysqli)
{
    $mysqli->query("UPDATE " . DB_PREFIX . "product SET status = '" . $product['status'] . "', price = '" . $product['price'] . "', quantity = '" . $product['quantity'] . "' WHERE product_id = '" . $product['id'] . "'");
    foreach ($product['options'] as $option) {
        $mysqli->query("UPDATE " . DB_PREFIX . "product_option_value SET price = '" . $option['price'] . "', quantity = '" . $option['quantity'] . "' WHERE product_id = '" . $product['id'] . "' AND sku = '" . $option['sku'] . "'");
    }
}

/*
 * The client wanted that any product with no stock left would be disabled from the shop. Smart.
 */
function disableProducts($mysqli)
{
    $mysqli->query("UPDATE " . DB_PREFIX . "product SET status = 0 WHERE quantity = 0");
}

/*
 * More maintenance, connected to the previous method. This will be called at the start of the
 * parsing and the parser will update the quantities as it churns along.
 */
function resetQuantity($mysqli)
{
    $mysqli->query("UPDATE " . DB_PREFIX . "product SET quantity = 0");
    $mysqli->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = 0");
}

/*
 * Basic getters for various things in OpenCart database.
 */

function getCategory($category, $mysqli)
{
    try {
        $query = $mysqli->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $category . "' ORDER BY `category_id` ASC ");
        $result = $query->fetch_assoc();
        return $result;

    } catch (Exception $e) {
    }
}

function getSubCategory($subcategory, $mysqli)
{

    $main = getCategory($subcategory['main_cat'], $mysqli);
    $sub = $mysqli->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $subcategory['sub_cat'] . "' ORDER BY `category_id` ASC ");
    $rows = array();
    while ($row = $sub->fetch_assoc()) {
        $rows[] = $row;
    }
    try {
        foreach ($rows as $row) {
            $query = $mysqli->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE parent_id = '" . $main['category_id'] . "' AND category_id = '" . $row['category_id'] . "'");
            $result = $query->fetch_array();
            if (empty($result) !== true) {
                return $result;
            }
        }
        return null;

    } catch (Exception $e) {
    }
}

function getManufacturer($manufacturer, $mysqli)
{
    try {
        $query = $mysqli->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $manufacturer . "'");
        $result = $query->fetch_array();
        return $result;

    } catch (Exception $e) {
    }
}

function getOption($option, $mysqli)
{
    try {
        $query = $mysqli->query("SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE name = '" . $option . "'");
        $result = $query->fetch_array();
        return $result;
    } catch (Exception $e) {

    }
}

function getProduct($product, $mysqli)
{
    try {
        $query = $mysqli->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE model = '" . $product . "'");
        $result = $query->fetch_array();
        return $result;
    } catch (Exception $e) {

    }
}

/*
 * Image downloader. It's straight-up lunacy to not save these images to S3 or something.
 * Can't exactly remember the reason why this decision was made. Especially the first run of
 * this script took ages to complete for obvious reasons.
 */

function getImage($url, $filename)
{
    if (!file_exists($filename)) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close($ch);

        $fp = fopen($filename, 'x');
        fwrite($fp, $raw);
        fclose($fp);
    }
}
/*
 * Does as it says.
 */
function countPrice($price)
{
    $result = (ceil((((float)$price * 1.6129) * 1.24)) - 0.1) / 1.24;
    return $result;
}
/*
 * Another dubious decision but since the client insisted on this...
 * I was against it since to me Google Translated text screams scams every-single-time.
 */
function translateToFinnish($string)
{
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, 'https://www.googleapis.com/language/translate/v2');
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_POSTFIELDS, array('key' => 'SWEDISHROLEPLAYER', 'q' => $string, 'source' => 'en', 'target' => 'fi'));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
    $response = curl_exec($handle);
    $serialized = json_decode($response);
    $result = $serialized->data->translations[0]->translatedText;
    return $result;

}
/*
 * Fix for the aforementioned
 */
function fixTranslation($mysqli){
    try {
        $query = $mysqli->query("SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = 93");
        while ($result = $query->fetch_array()) {
            $secondQuery = $mysqli->query("SELECT description FROM " . DB_PREFIX . "product_description WHERE product_id = '" . $result['product_id'] . "' AND language_id = 2");
            $secondResult = $secondQuery->fetch_array();
            if (strpos(strtolower($secondResult[0]), "naisten") !== false) {
                $replacement = str_replace("Naisten", "Miesten", $secondResult[0]);
                $mysqli->query("UPDATE " . DB_PREFIX . "product_description SET description = '" . $mysqli->real_escape_string($replacement). "' WHERE product_id = '" . $result['product_id'] . "' AND language_id = 2");
            }
        }
    } catch (Exception $e) {
        var_dump($e);
    }
}


//-------------------------------
//---- Start of the parsing  ----
//-------------------------------

resetQuantity($mysqli);

foreach ($scanned_directory as $file) {
    //echo '<hr/><br/>' . $file . '<br/>';
    //downloadFile();
    $categories = array();
    $subcategories = array();
    $gatherer = array();
    $brands = array();
    $sizes = array();

    $i = 0;
    $throttleCounter = 0;

    $addCategory = array();
    $addManufacturer = array();
    $addProduct = array();
    $addOption = array();

    $lengthCounter = 0;


    $objects = simplexml_load_file($directory . $file);


/*
 * This structure is to gather all the different sizes, categories and brands that the XML-file included.
 * These are then imported to OpenCart DB if any of them are not already in the db.
 */
    foreach ($objects as $items) {
        foreach ($items as $item) {
            foreach ($item->tags as $tags) {
                foreach ($tags as $tag) {
                    if ($tag->translations->translation->description == 'Gender') {
                        $categories[$i] = (string)$tag->value->translations->translation->description;
                    }
                    if ($tag->translations->translation->description == 'Subcategories') {
                        $gatherer[$i] = (string)$tag->value->translations->translation->description;
                    }
                }
            }
            if (isset($gatherer[$i]) && isset($categories[$i])) {
                $subcategories[$i]['main_cat'] = $categories[$i];
                $subcategories[$i]['sub_cat'] = $gatherer[$i];
            } else {
                $subcategories[$i]['sub_cat'] = $gatherer[$i];
            }
            foreach ($item->models as $models) {
                foreach ($models as $model) {
                    $sizes[] = (string)$model->size;
                }
            }
            $brands[] = $item->brand;
            $i++;
        }
    }

    // Removing duplicates
    $brands = array_unique($brands);
    $sizes = array_unique($sizes);
    $categories = array_unique($categories);
    $subcategories = array_map("unserialize", array_unique(array_map("serialize", $subcategories)));


    /*
     * Check for existing categories, brands and sizes and add a new one if it's missing.
     */
    foreach ($categories as $category) {
        $existing = getCategory($category, $mysqli);
        if (empty($existing) === true) {

            /*
             * Reason for this sleep and this counter is in Google Translate and how not to overload it.
             * Also further prolongs the runtime.
             */
            if ($lengthCounter > 8000) {
                sleep(100);
                $lengthCounter = 0;
            }
            $lengthCounter = $lengthCounter + strlen($category);
            $addCategory['category_description'][1] = array(
                'name' => $category,
                'meta_description' => '',
                'meta_keyword' => '',
                'description' => ''
            );
            $addCategory['category_description'][2] = array(
                'name' => utf8_decode(translateToFinnish($category)),
                'meta_description' => '',
                'meta_keyword' => '',
                'description' => ''
            );
            $addCategory['path'] = '';
            $addCategory['parent_id'] = 0;
            $addCategory['filter'] = '';
            $addCategory['category_store'] = array(
                0 => 0
            );
            $addCategory['keyword'] = '';
            $addCategory['image'] = '';
            $addCategory['column'] = '1';
            $addCategory['sort_order'] = '0';
            $addCategory['status'] = '1';
            $addCategory['category_layout'] = array(
                0 => array(
                    'layout_id' => '')
            );

            insertCategory($addCategory, $mysqli);
            //var_dump($addCategory);
        }
    }


    foreach ($subcategories as $subcategory) {
        $existing = getSubCategory($subcategory, $mysqli);
        if (empty($existing) === true
            && $subcategory['sub_cat'] != "Luggage"
            && $subcategory['sub_cat'] != "Watches"
            && $subcategory['sub_cat'] != "Briefcases") {
            if ($lengthCounter > 8000) {
                sleep(100);
                $lengthCounter = 0;
            }
            $lengthCounter = $lengthCounter + strlen($subcategory['sub_cat']);
            $addCategory['category_description'][1] = array(
                'name' => $subcategory['sub_cat'],
                'meta_description' => '',
                'meta_keyword' => '',
                'description' => ''
            );
            $addCategory['category_description'][2] = array(
                'name' => translateToFinnish($subcategory['sub_cat']),
                'meta_description' => '',
                'meta_keyword' => '',
                'description' => ''
            );
            $addCategory['path'] = '';
            $addCategory['parent_id'] = '';
            $addCategory['filter'] = '';
            $addCategory['category_store'] = array(
                0 => 0
            );
            $addCategory['keyword'] = '';
            $addCategory['image'] = '';
            $addCategory['column'] = '1';
            $addCategory['sort_order'] = '0';
            $addCategory['status'] = '1';
            $addCategory['category_layout'] = array(
                0 => array(
                    'layout_id' => '')
            );
            $main = getCategory($subcategory['main_cat'], $mysqli);
            $addCategory['parent_id'] = $main['category_id'];
            insertCategory($addCategory, $mysqli);
        }
    }

    foreach ($brands as $brand) {
        $existing = getManufacturer($brand, $mysqli);
        if (empty($existing) === true) {
            $addManufacturer['name'] = $brand;
            $addManufacturer['manufacturer_store'] = array(0 => 0);
            $addManufacturer['keyword'] = '';
            $addManufacturer['image'] = '';
            $addManufacturer['sort_order'] = '';
            insertManufacturer($addManufacturer, $mysqli);
        }
    }

    /*
     * It's good to mention here that the sizes DB involved every single size.
     * XL, S, shoe sizes in American, British, Finnish etc. standards
     */

    foreach ($sizes as $size) {
        $existing = getOption($size, $mysqli);
        if (empty($existing) === true) {
            $addOption['option_id'] = 11;
            $addOption['option_value'] = array(
                'option_value_id' => '',
                'option_value_description' => array(
                    0 => $size
                ),
                'image' => '',
                'sort_order' => ''
            );
            insertOptionValue($addOption, $mysqli);
        }
    }

    /*
     * The meat of the script, the actual product.
     */

    foreach ($objects as $items) {
        foreach ($items as $item) {
            $addProduct = array();
            $editProduct = array();
            $sub = null;
            $existing = getProduct((string)$item->id, $mysqli);
            foreach ($item->tags as $tags) {
                foreach ($tags as $tag) {
                    if ((string)$tag->translations->translation->description === 'Subcategories') {
                        $sub = (string)$tag->value->translations->translation->description;
                    }
                }
            }
            if (empty($existing) === true
                && (int)$item->availability != 0
                && $sub != "Watches"
                && $sub != "Briefcases"
                && $sub != "Luggage")
            {
                if ($lengthCounter > 8000) {
                    sleep(100);
                    $lengthCounter = 0;
                }
                $lengthCounter = $lengthCounter + strlen((string)$item->name) + strlen((string)$item->description);
                $addProduct['product_description'] = array(
                    1 => array(
                        'name' => (string)$item->brand . " " . (string)$item->name,
                        'meta_description' => '',
                        'meta_keyword' => '',
                        'description' => (string)$item->description,
                        'tag' => ''
                    ),
                    2 => array(
                        'name' => (string)$item->brand . " " . (string)$item->name,
                        'meta_description' => '',
                        'meta_keyword' => '',
                        'description' => utf8_decode(translateToFinnish((string)$item->description)),
                        'tag' => ''
                    )
                );
                $addProduct['model'] = (string)$item->id;
                $addProduct['sku'] = (string)$item->id;
                $addProduct['upc'] = '';
                $addProduct['ean'] = '';
                $addProduct['jan'] = '';
                $addProduct['isbn'] = '';
                $addProduct['mpn'] = '';
                $addProduct['location'] = '';
                $addProduct['price'] = (float)countPrice($item->taxable);
                $addProduct['price_gross'] = '';
                $addProduct['tax_class_id'] = 9;
                $addProduct['quantity'] = (int)$item->availability;
                $addProduct['minimum'] = 1;
                $addProduct['subtract'] = 1;
                $addProduct['stock_status_id'] = 5;
                $addProduct['shipping'] = 1;
                $addProduct['keyword'] = '';
                $images = array();
                $i = 0;
                foreach ($item->pictures as $pictures) {
                    foreach ($pictures as $picture) {
                        $url = "https://www.xxxxxxxxxxxxxxxxxx.com" . (string)$picture->url;
                        //$filepath = "/var/www/htdocs/image" . (string)$picture->url;
                        $filepath = "/usr/share/nginx/html/image" . (string)$picture->url;
                        getImage($url, $filepath);
                        $addProduct['image'] = (string)$picture->url;
                        break;
                    }
                    foreach ($pictures as $picture) {
                        if ($i !== 0) {
                            $url = "https://www.xxxxxxxxxxxxxxxxxx.com" . (string)$picture->url;
                            //$filepath = "/var/www/htdocs/image" . (string)$picture->url;
                            $filepath = "/usr/share/nginx/html/image" . (string)$picture->url;
                            getImage($url, $filepath);
                            $images[] = array(
                                'image' => (string)$picture->url,
                                'sort_order' => ''
                            );
                        }
                        $i++;
                    }
                }
                $addProduct['product_image'] = $images;
                $addProduct['date_available'] = date('Y-m-d');
                $addProduct['length'] = '';
                $addProduct['width'] = '';
                $addProduct['height'] = '';
                $addProduct['weight'] = (string)$item->weight;
                $addProduct['length_class_id'] = 1;
                $addProduct['weight_class_id'] = 1;
                $addProduct['status'] = 1;
                $addProduct['sort_order'] = 1;
                $addProduct['manufacturer'] = (string)$item->brand;
                $productManufacturer = getManufacturer($item->brand, $mysqli);
                $addProduct['manufacturer_id'] = $productManufacturer['manufacturer_id'];
                $addProduct['product_category'] = array();
                $sub = array();
                foreach ($item->tags as $tags) {
                    foreach ($tags as $tag) {
                        if ((string)$tag->translations->translation->description == 'Gender') {
                            $sub['main_cat'] = (string)$tag->value->translations->translation->description;
                        }
                        if ((string)$tag->translations->translation->description === 'Subcategories') {
                            $sub['sub_cat'] = (string)$tag->value->translations->translation->description;
                        }
                    }
                }
                $subCat = getSubCategory($sub, $mysqli);
                $addProduct['product_category'][] = $subCat['category_id'];
                $addProduct['filter'] = '';
                $addProduct['product_store'] = array(
                    0 => 0
                );
                $addProduct['download'] = '';
                $addProduct['related'] = '';
                $addProduct['option'] = 'Koko';
                $productOptionValue = array();
                $productOptionGross = null;
                $counter = 0;

                /*
                 * Options here are basically different color-options of the same product.
                 */
                foreach ($item->models as $models) {
                    foreach ($models as $model) {
                        $optionID = getOption((string)$model->size, $mysqli);
                        $productOptionValue[] = array(
                            'option_value_id' => $optionID['option_value_id'],
                            'product_option_value_id' => '',
                            'quantity' => (string)$model->availability,
                            'price_prefix' => '+',
                            'subtract' => 1,
                            'price' => 0,
                            'points_prefix' => '+',
                            'points' => '',
                            'weight_prefix' => '+',
                            'weight' => '',
                            'sku' => (string)$model->id

                        );
                        $productOptionGross = array(
                            $counter => ''
                        );
                        $counter++;
                    }
                }
                $addProduct['product_option'] = array(
                    0 => array(
                        "product_option_id" => '',
                        'name' => 'Koko',
                        'option_id' => 11,
                        'type' => 'select',
                        'required' => 1,
                        'product_option_value' => $productOptionValue
                    )
                );
                $addProduct['product_option_price_gross'] = $productOptionGross;
                $addProduct['points'] = '';
                $addProduct['product_reward'] = array(
                    1 => array(
                        'points' => ''
                    )
                );
                $addProduct['product_layout'] = array(
                    0 => array(
                        'layout_id' => ''
                    )
                );

                addProduct($addProduct, $mysqli);
            } else {
                /*
                 * If product is already in the database, just update it and activate it.
                 */
                if ((int)$item->availability != 0) {
                    $productOptionValue = array();
                    $editProduct['id'] = $existing['product_id'];
                    $editProduct['price'] = (float)countPrice($item->taxable);
                    $editProduct['quantity'] = (int)$item->availability;
                    $editProduct['status'] = 1;
                    foreach ($item->models as $models) {
                        foreach ($models as $model) {
                            $productOptionValue[] = array(
                                'price' => 0,
                                'quantity' => (string)$model->availability,
                                'sku' => (string)$model->id
                            );
                        }
                    }
                    $editProduct['options'] = $productOptionValue;
                    updateProduct($editProduct, $mysqli);
                }
            }
        }

    }
    /*
     * Maintenance for shop. Sweep products no longer in stock under the rug.
     */
    disableProducts($mysqli);
    unlink($directory . $file);
}

fixTranslation($mysqli);
mysqli_close($mysqli);

