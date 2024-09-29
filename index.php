<?php
/*
Plugin Name: WooCommerce Dynamic Product Export
Description: Export WooCommerce products with selected fields (including custom fields) in CSV format using a drag-and-drop interface.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Step 1: Add an admin menu for the export tool
function woocommerce_export_menu() {
    add_menu_page(
        'WooCommerce Product Export',  // Page title
        'Product Export',               // Menu title
        'manage_options',               // Capability
        'woocommerce-product-export',   // Menu slug
        'woocommerce_export_page',      // Callback function
        'dashicons-download',           // Icon
        20                              // Position
    );
}
add_action('admin_menu', 'woocommerce_export_menu');

// Function to retrieve dynamic core fields matching WooCommerce Import Suite headers
function get_woocommerce_core_fields() {
    return array(
        'ID' => 'ID',
        'Type' => 'product_type', // Product Type
        'SKU' => 'sku', // SKU
        'Name' => 'name', // Product Name
        'Published' => 'status', // Published Status
        'Is featured?' => 'featured', // Featured
        'Visibility in catalog' => 'catalog_visibility', // Catalog Visibility
        'Short description' => 'short_description', // Short Description
        'Description' => 'description', // Description
        'Regular price' => 'regular_price', // Regular Price
        'Sale price' => 'sale_price', // Sale Price
        'Categories' => 'categories', // Categories
        'Tags' => 'tags', // Tags
        'Stock' => 'stock', // Stock Quantity
        'Weight' => 'weight', // Weight
        'Length' => 'length', // Length
        'Width' => 'width', // Width
        'Height' => 'height', // Height
        'Downloadable' => 'downloadable', // Downloadable
        'Virtual' => 'virtual', // Virtual Product
        'Tax status' => 'tax_status', // Tax Status
        'Tax class' => 'tax_class', // Tax Class
        'Images' => 'images', // Product Images
        'Download limit' => 'download_limit', // Download Limit
        'Download expiry' => 'download_expiry', // Download Expiry
        'Parent' => 'parent', // Parent Product ID
        'Grouped products' => 'grouped_products', // Grouped Products
        'Upsells' => 'upsell_ids', // Upsells
        'Cross-sells' => 'cross_sell_ids', // Cross-sells
        'Position' => 'menu_order', // Menu Order
        // Here we dynamically retrieve attribute keys
        'Attributes' => 'attributes', // Custom Attribute Handling
    );
}

// In the export page function
function woocommerce_export_page() {

    $product_count = wp_count_posts('product')->publish;
    ?>
    <div class="wrap">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

        <h1>WooCommerce Product Export</h1>

        <!-- Display the total product count -->
        <div>
            <h3>Total Products Available: <?php echo $product_count; ?></h3>
        </div>

        <p>Select the fields you want to export by dragging them to the "Selected Fields" box.</p>
        <div id="export-tool">
            <div style="display: flex;">
                <!-- Available Fields -->
                <div style="flex: 1; padding: 10px;">
                    <h2>Available Fields</h2>
                    <ul class="field-list" id="core-fields">
                        <h3>Core Fields</h3>
                        <?php
                        // Use dynamic core fields
                        $core_fields = get_woocommerce_core_fields();
                        foreach ($core_fields as $label => $field_key) {
                            echo "<li class='field-item' data-field='$field_key'>$label</li>";
                        }
                        ?>
                    </ul>

                    <ul class="field-list" id="custom-fields">
                        <h3>Custom Fields</h3>
                        <?php
                        global $wpdb;

                        // Get custom fields (meta keys) related to WooCommerce products
                        $custom_meta_keys = $wpdb->get_col("SELECT DISTINCT meta_key FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND meta_key NOT LIKE '\_%' LIMIT 100");

                        foreach ($custom_meta_keys as $custom_field) {
                            echo "<li class='field-item' data-field='$custom_field'>$custom_field</li>";
                        }
                        ?>
                    </ul>
                </div>

                <!-- Selected Fields -->
                <div style="flex: 1; padding: 10px;">
                    <h2>Selected Fields</h2>
                    <ul id="selected-fields" class="field-list">
                        <!-- Fields dragged here will be exported -->
                    </ul>
                </div>
            </div>

            <!-- Export Button -->
            <form method="post" action="">
                <input type="hidden" id="selected_fields_input" name="selected_fields_input" value="">
                <?php submit_button('Export to CSV'); ?>
                <!-- Loader -->
                <div id="loader" style="display:none;">
                    <p>Loading... Please wait</p>
                </div>
            </form>
        </div>
    </div>

    <style>
        .field-list {
            border: 1px solid #ccc;
            min-height: 200px;
            padding: 10px;
            background-color: #f9f9f9;
            list-style-type: none;
        }
        .field-item {
            cursor: pointer;
            padding: 5px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            background-color: #fff;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let selectedFields = document.getElementById('selected-fields');
            let selectedFieldsInput = document.getElementById('selected_fields_input');

            // Enable dragging of fields
            document.querySelectorAll('.field-item').forEach(item => {
                item.setAttribute('draggable', true);
                item.addEventListener('dragstart', function (e) {
                    e.dataTransfer.setData('text/plain', e.target.dataset.field);
                });
            });

            // Handle drop into the selected fields list
            selectedFields.addEventListener('dragover', function (e) {
                e.preventDefault();
            });
            selectedFields.addEventListener('drop', function (e) {
                e.preventDefault();
                let field = e.dataTransfer.getData('text/plain');
                let listItem = document.createElement('li');
                listItem.textContent = field;
                listItem.classList.add('field-item');
                listItem.setAttribute('data-field', field);
                selectedFields.appendChild(listItem);
                updateSelectedFields();
            });

            // Update the hidden input with selected fields
            function updateSelectedFields() {
                let selected = [];
                selectedFields.querySelectorAll('.field-item').forEach(item => {
                    selected.push(item.dataset.field);
                });
                selectedFieldsInput.value = selected.join(',');
            }
        });
    </script>
    <?php
}

// Step 3: Handle the form submission and CSV export
function woocommerce_export_to_csv() {
    if (isset($_POST['selected_fields_input']) && !empty($_POST['selected_fields_input'])) {
        $selected_fields = explode(',', sanitize_text_field($_POST['selected_fields_input']));

        if (empty($selected_fields)) {
            return;
        }

        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=woocommerce_products_export.csv');

        $output = fopen('php://output', 'w');

        // Initialize an array to hold column headers
        $csv_headers = $selected_fields;

        // Get WooCommerce products
        $args = array('post_type' => 'product', 'posts_per_page' => -1);
        $products = get_posts($args);

        // Create an array to keep track of attribute headers
        $attribute_headers = [];

        // First pass: Collect headers for attributes
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            $attributes = $product->get_attributes();
            foreach ($attributes as $attribute_name => $attribute) {
                // Check if attribute is visible on the product page
                if (!$attribute->get_variation() && $attribute->get_visible()) {
                    // Add unique attribute headers
                    $attribute_headers[] = $attribute->get_name();
                }
            }
        }

        // Merge attribute headers with selected fields headers
        $csv_headers = array_merge($csv_headers, array_unique($attribute_headers));
        fputcsv($output, $csv_headers);

        // Second pass: Collect data
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            $data = [];

            // Fetch selected fields dynamically
            foreach ($selected_fields as $field) {
                $field_value = '';

                switch ($field) {
                    case 'ID':
                        $field_value = $product->get_id();
                        break;
                    case 'Name':
                        $field_value = $product->get_name();
                        break;
                    case 'SKU':
                        $field_value = $product->get_sku();
                        break;
                    case 'Regular price':
                        $field_value = $product->get_regular_price();
                        break;
                    case 'Sale price':
                        $field_value = $product->get_sale_price();
                        break;
                    case 'Categories':
                        $field_value = wp_strip_all_tags(wp_get_document_title()); // Categories should be replaced with an appropriate function if you require it
                        break;
                    case 'Tags':
                        $field_value = wp_strip_all_tags(wp_get_document_title()); // Tags should be replaced with an appropriate function if you require it
                        break;
                    case 'Stock':
                        $field_value = $product->get_stock_quantity();
                        break;
                    case 'Weight':
                        $field_value = $product->get_weight();
                        break;
                    case 'Length':
                        $field_value = $product->get_length();
                        break;
                    case 'Width':
                        $field_value = $product->get_width();
                        break;
                    case 'Height':
                        $field_value = $product->get_height();
                        break;
                    case 'Images':
                        $images = $product->get_gallery_image_ids();
                        $field_value = implode(', ', array_map('wp_get_attachment_url', $images));
                        break;
                    default:
                        // Custom fields
                        $field_value = get_post_meta($product_post->ID, $field, true);
                }

                $data[] = $field_value;
            }

            // Add attributes
            foreach (array_unique($attribute_headers) as $attribute_name) {
                $attribute_value = $product->get_attribute($attribute_name);
                $data[] = $attribute_value;
            }

            fputcsv($output, $data);
        }

        fclose($output);
        exit;
    }
}
add_action('admin_init', 'woocommerce_export_to_csv');







add_action('admin_enqueue_scripts', 'enqueue_export_scripts');

function enqueue_export_scripts() {
    wp_enqueue_script('ajax-export', plugin_dir_url(__FILE__) . 'js/ajax-export.js', array('jquery'), null, true);
    wp_localize_script('ajax-export', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}





add_action('wp_ajax_export_products', 'handle_ajax_export_products');

function handle_ajax_export_products() {
    // Check for permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized.'));
    }

    // Get the selected fields from the AJAX request
    $selected_fields = isset($_POST['fields']) ? $_POST['fields'] : array();

    // Ensure fields were selected
    if (empty($selected_fields)) {
        wp_send_json_error(array('message' => 'No fields selected.'));
    }

    // Prepare CSV content
    $csv_data = array();
    $csv_header = $selected_fields; // CSV header will be the selected fields
    $csv_data[] = $csv_header;

    // Get products and append their data for selected fields
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    );
    $products = get_posts($args);

    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        $row = array();

        foreach ($selected_fields as $field) {
            switch ($field) {
                case 'ID':
                    $row[] = $product->get_id();
                    break;
                case 'product_type':
                    $row[] = $product->get_type(); // Product Type
                    break;
                case 'sku':
                    $row[] = $product->get_sku(); // SKU
                    break;
                case 'name':
                    $row[] = $product->get_name(); // Product Name
                    break;
                case 'status':
                    $row[] = $product->get_date_created() ? 'yes' : 'no'; // Published Status
                    break;
                case 'featured':
                    $row[] = $product->is_featured() ? 'yes' : 'no'; // Featured
                    break;
                case 'catalog_visibility':
                    $row[] = $product->get_catalog_visibility(); // Catalog Visibility
                    break;
                case 'short_description':
                    $row[] = $product->get_short_description(); // Short Description
                    break;
                case 'description':
                    $row[] = $product->get_description(); // Description
                    break;
                case 'regular_price':
                    $row[] = $product->get_regular_price(); // Regular Price
                    break;
                case 'sale_price':
                    $row[] = $product->get_sale_price(); // Sale Price
                    break;
                case 'categories':
                    $row[] = strip_tags(get_the_term_list($product->get_id(), 'product_cat', '', ', ', '')); // Categories
                    break;
                case 'tags':
                    $row[] = strip_tags(get_the_term_list($product->get_id(), 'product_tag', '', ', ', '')); // Tags
                    break;
                case 'stock':
                    $row[] = $product->get_stock_quantity(); // Stock Quantity
                    break;
                case 'weight':
                    $row[] = $product->get_weight(); // Weight
                    break;
                case 'length':
                    $row[] = $product->get_length(); // Length
                    break;
                case 'width':
                    $row[] = $product->get_width(); // Width
                    break;
                case 'height':
                    $row[] = $product->get_height(); // Height
                    break;
                case 'downloadable':
                    $row[] = $product->is_downloadable() ? 'yes' : 'no'; // Downloadable
                    break;
                case 'virtual':
                    $row[] = $product->is_virtual() ? 'yes' : 'no'; // Virtual Product
                    break;
                case 'tax_status':
                    $row[] = $product->get_tax_status(); // Tax Status
                    break;
                case 'tax_class':
                    $row[] = $product->get_tax_class(); // Tax Class
                    break;
                case 'images':
                    $image_id = $product->get_image_id();
                    $row[] = wp_get_attachment_url($image_id); // Product Images
                    break;
                case 'download_limit':
                    $row[] = get_post_meta($product->get_id(), '_download_limit', true); // Download Limit
                    break;
                case 'download_expiry':
                    $row[] = get_post_meta($product->get_id(), '_download_expiry', true); // Download Expiry
                    break;
                case 'parent':
                    $row[] = $product->get_parent_id(); // Parent Product ID
                    break;
                case 'grouped_products':
                    $row[] = implode(', ', $product->get_type() === 'grouped' ? $product->get_children() : []); // Grouped Products
                    break;
                case 'upsell_ids':
                    $row[] = implode(', ', $product->get_upsell_ids()); // Upsells
                    break;
                case 'cross_sell_ids':
                    $row[] = implode(', ', $product->get_cross_sell_ids()); // Cross-sells
                    break;
                case 'menu_order':
                    $row[] = $product->get_menu_order(); // Menu Order
                    break;
                case 'attributes':
                    $attributes = $product->get_attributes();
                    $row[] = implode(', ', array_map(function($attr) {
                        return $attr->get_name() . ': ' . implode(', ', $attr->get_options());
                    }, $attributes));
                    break;
                // Additional fields can be handled here
                default:
                    $row[] = get_post_meta($product->get_id(), $field, true); // Get custom fields
                    break;
            }
        }

        $csv_data[] = $row;
    }

    // Generate CSV file
    $file_path = wp_upload_dir()['path'] . '/products_export.csv';
    $file = fopen($file_path, 'w');

    foreach ($csv_data as $csv_row) {
        fputcsv($file, $csv_row);
    }
    fclose($file);

    // Return file URL for download
    $file_url = wp_upload_dir()['url'] . '/products_export.csv';
    wp_send_json_success(array('file_url' => $file_url));
}



