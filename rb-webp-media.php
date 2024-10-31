<?php
/**
 * Plugin Name: RB Webp Media
 * Author: rambu.dev
 * Description: Override current upload media process, auto convert new upload image from jpg,png,jpeg to  webp type to reduce size and improve load speed
 * Author URI: rambu.dev
 * Version: 1.0.0.0
 */

if ( !function_exists('rb_webp_pre_move_uploaded_file') ) {
    function rb_webp_pre_move_uploaded_file($move_new_file, $file, $new_file, $type) {
        $time = current_time( 'mysql' );
        if( isset($_REQUEST['post_id']) ) {
            $post_id = intval($_REQUEST['post_id']);
            $post = get_post($post_id);
            if ( $post && substr( $post->post_date, 0, 4 ) > 0 ) {
                $time = $post->post_date;
            }
        }
        $quality = 80;
        $wp_filetype     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
        if ( 0 === strpos($file['type'], 'image/') && 0 === strpos($wp_filetype['type'], 'image/') ) {
            $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
            $webp_name = str_replace($wp_filetype['ext'], 'webp', $file['name']);


            $uploads = wp_upload_dir( time() );
            $webp_name = wp_unique_filename( $uploads['path'], $webp_name );

            $newfile = trailingslashit(dirname($file['tmp_name'])) . $webp_name;
            
            imagewebp($image, $newfile, $quality);
            imagedestroy($image);
            $uploads = wp_upload_dir( $time );
            if ( ! ( $uploads && false === $uploads['error'] ) ) {
                return null;
            }
            // Move the file to the uploads dir.
            $new_upload_file = $uploads['path'] . "/$webp_name";
            $moved_new_file = @copy( $newfile, $new_upload_file );

            if ( false === $moved_new_file ) {
                return null;
            }
            
            return $new_upload_file;
        }
        
        return $move_new_file;
    }
    add_filter( 'pre_move_uploaded_file', 'rb_webp_pre_move_uploaded_file', 10, 4);
}

if ( !function_exists('rb_webp_wp_handle_upload') ) {
    function rb_webp_wp_handle_upload($file, $type) {
        if ( 0 === strpos($file['type'], 'image/') ) {
            $info = pathinfo($file['file']);
            $file['file'] = str_replace($info['extension'], 'webp', $file['file']);
            $file['type'] = 'image/webp';
            $file['url'] = str_replace($info['extension'], 'webp', $file['url']);
        }
        
        return $file;
    }
    add_filter( 'wp_handle_upload', 'rb_webp_wp_handle_upload', 10, 2);
}