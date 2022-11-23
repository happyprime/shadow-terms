<?php
/**
 * Plugin Name:  Shadow Terms
 * Description:  Use terms from generated taxonomies to associate related content.
 * Version:      0.0.2
 * Plugin URI:   https://github.com/happyprime/shadow-terms/
 * Author:       Happy Prime
 * Author URI:   https://happyprime.co
 * Text Domain:  shadow-terms
 * Requires PHP: 7.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package shadow-terms
 */

namespace ShadowTerms;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/sync.php';
require_once __DIR__ . '/includes/taxonomy.php';
