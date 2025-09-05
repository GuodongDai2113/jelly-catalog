<?php

/**
 * includes\jc-functions.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.05 15:56
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * Get the placeholder image URL either from media, or use the fallback image.
 *
 * @param string $size Thumbnail size to use.
 * @return string
 */
function jc_placeholder_img_src() {
	$src = JELLY_CATALOG_PLUGIN_URL . 'assets/images/placeholder.webp';
	return  $src;
}