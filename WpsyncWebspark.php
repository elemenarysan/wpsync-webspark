<?php

class WpsyncWebspark {

    public static $hookBeginName;
    public static $adminMenuSlug = 'wc-product-import';

    public static function plugin_activation()
    {
        if(!is_plugin_active('woocommerce/woocommerce.php') ){
            wp_die('Требуется установка WooCommerce');
        }
    }

    public static function plugin_deactivation()
    {

    }

    public static function plugin_uninstall()
    {

    }


    public static function pageRender()
    {
        echo '<h3>Импорт даных</h3>';
        echo '<div>';
        if(static::pidFileCheck()){
            echo '<div><b>Статус:</b><i>Запущено</i></div>';
            echo '<a href="'. esc_url( admin_url('admin-post.php') ).'?action=import-stop">Остановить</a>';
        }   else {
            echo '<div><b>Статус:</b><i>Остановлено</i></div>';
            echo '<a href="'. esc_url( admin_url('admin-post.php') ).'?action=import-start">Начать</a>';
        }
        echo '</div>';
        echo '<div>';
        echo '<a href="'. esc_url( admin_url('admin-post.php') ).'?action=import-log" target="_blank">Лог импорта</a>';
        echo '</div>';
    }

    public static function addMenu($inSubMenu = true)
    {
        add_submenu_page(
            ($inSubMenu?'edit.php?post_type=product':''),
            'Импорт товаров',
            'Импорт товаров',
            'manage_options',
            WpsyncWebspark::$adminMenuSlug ,
            array( 'WpsyncWebspark', 'pageRender' )
        );
    }

    public static function importStartAction()
    {
        if(!static::pidFileCheck()){
            $output = [];
            $res = '';
            $execs = [];
            $execs[] = 'php';
            $execs[] = '"'.escapeshellcmd(ABSPATH.'wp-cli.phar').'"';
            $execs[] = 'products import & echo $!';
            $command = implode(' ', $execs);
            $handl = popen($command, 'r');
            $read = fgets($handle);
            pclose($handl);

        }
        static::redirectPage();
    }

    public static function importStopAction()
    {
        static::stopImport();
        static::redirectPage();
    }

    public static function importLogAction()
    {
        require_once( plugin_dir_path( __FILE__ ) . 'Loger.php' );
        echo(file_get_contents(Loger::filePath()));
        exit;
    }

    public static function redirectPage()
    {
        WpsyncWebspark::addMenu(false);
        wp_redirect( menu_page_url(WpsyncWebspark::$adminMenuSlug, false), 302 );
        exit;
    }

    public static function pidFileCheck()
    {
        $result = false;
        $out = [];
        $command = 'ps -ax | grep php | grep wp-cli.phar | grep products | grep import | grep -v " ps "';
        $res = exec($command, $out);
        if($res){
            $res = trim($res);
            $resArray = explode(' ', $res);
            $result = reset($resArray);
;        }
        return $result;
    }

    public static function beginImport()
    {
        require_once( plugin_dir_path( __FILE__ ) . 'Loger.php' );

        require_once(ABSPATH.'/wp-admin/includes/image.php');
        require_once(ABSPATH.'/wp-admin/includes/file.php');
        require_once(ABSPATH.'/wp-admin/includes/media.php');
        function uploadMedia($image_url) {
            $media = media_sideload_image($image_url,0);
            if($media instanceof WP_Error){
                return 0;
            }
            $attachments = get_posts(array(
                'post_type' => 'attachment',
                'post_status' => null,
                'post_parent' => 0,
                'orderby' => 'post_date',
                'order' => 'DESC'
            ));
            return $attachments[0]->ID;
        }

         Loger::info('Начало загрузки данных');

        if(empty(WC_PRODUCTS_IMPORT_URL)){
            wp_die(Loger::error('Не настроенна переменная окружения импорта'));
        }

        $data = file_get_contents(WC_PRODUCTS_IMPORT_URL);
        if(empty($data)){
            wp_die(Loger::error('Данные не загружены.'));
        }
        $data = json_decode($data);

        if(empty($data) || JSON_ERROR_NONE != json_last_error()){
             wp_die(Loger::error('Данные не Обработались'));
        }

        Loger::info('Записей пришло '.count($data));
        Loger::info('Начало обработки данных');
        $loadedCount = 0;
        $skuAll = [];
        foreach($data as $item){
            $skuAll[] = $item->sku;
            //continue;
            $product_id = wc_get_product_id_by_sku( $item->sku );
            $saved = false;
            if(empty($product_id)){
                $objProduct = new WC_Product();
                $objProduct->set_sku($item->sku); //can be blank in case you don't have sku, but You can't add duplicate sku's
                if(!empty($item->picture)){
                    $mediaID = uploadMedia($item->picture);
                    if($mediaID){
                        $objProduct->set_image_id($mediaID);
                    }
                    //$objProduct->set_gallery_image_ids($productImagesIDs);
                }
                $objProduct->set_status("publish");  // can be publish,draft or any wordpress post status
                $objProduct->set_catalog_visibility('visible'); // add the product visibility status
                //$objProduct->set_backorders('no');
                //$objProduct->set_reviews_allowed(true);
                //$objProduct->set_sold_individually(false);
                //$objProduct->set_category_ids(array(1,2,3)); // array of category ids, You can get category id from WooCommerce Product Category Section of Wordpress Admin
                $saved = true;
            } else {
                $objProduct = wc_get_product( $product_id );
            }
            if($item->name != $objProduct->get_name()){
                $objProduct->set_name($item->name);
                $saved = true;
            }
            if($item->description != $objProduct->get_description()){
                $objProduct->set_description($item->description);
                $saved = true;
            }
            if($item->price != $objProduct->get_price()){
                $objProduct->set_price($item->price); // set product price
            }
                //$objProduct->set_regular_price($item->description); // set product regular price
            if(!empty($item->in_stock)){
                if($item->in_stock != $objProduct->get_stock_quantity()){
                    $objProduct->set_manage_stock(true); // true or false
                    $objProduct->set_stock_quantity($item->in_stock);
                    $objProduct->set_stock_status('instock'); // in stock or out of stock value
                    $saved = true;
                }
            }
            if($saved){
                $product_id = $objProduct->save();
            }
            $loadedCount++;
            if(!($loadedCount % 250)){
                Loger::info('Загруженно данных '.$loadedCount);
            }

        }
        unset($data);
        //определить товары, которых есть в базе, но не пришли новые
        if(count($skuAll)){
            global $wpdb;
            $query = 'select product_id FROM ' . $wpdb->prefix . 'wc_product_meta_lookup ';
            $query .=  'WHERE sku not in(';
            foreach($skuAll as $skuOne){
                $query .= '%s,';
            }
            $query = substr($query, 0, strlen($query)-1);
            $query .= ')';
            $query = $wpdb->prepare($query,$skuAll);
            $result = $wpdb->get_results( $query, OBJECT );
            unset($query);
            Loger::info('Для удаления записей  '. count($result));
            foreach($result as $item){
                $objProduct = wc_get_product( $item->product_id );
                if($objProduct){
                    $objProduct->delete(true);
                }
            }
            Loger::info('Записи удалены');
        }
        Loger::info('Окончание загрузки данных, загруженно '.$loadedCount);

    }

    public static function stopImport()
    {
        require_once( plugin_dir_path( __FILE__ ) . 'Loger.php' );
        Loger::info('Остановка загрузки данных');

        $pid = static::pidFileCheck();
        if($pid){
            exec('kill "'.escapeshellcmd($pid).'"');            
        }
    }
}
