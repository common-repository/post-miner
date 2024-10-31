<?php
/*
Plugin Name: Post Miner
Plugin URI: http://postminer.blogspot.com/
Description: Post Miner is a content recommendation engine for WordPress. The plugin tries to discover user's preferences by analyzing his behaviour on the page. Recommended content is the closest Euclidean distance between user interest vector snd a post content vector.
Author: Lukasz Kujawa <lukasz.f24@gmail.com>
Version: 1.0.3
*/

defined('POST_MINER__PATH') || 
    define( 'POST_MINER__PATH', plugin_dir_path( __FILE__ ) . '/PostMiner/' );

defined('POST_MINER__FILE') ||
    define( 'POST_MINER__FILE', __FILE__ );

require_once POST_MINER__PATH . 'Plugin.php';

/**
 * Create a plugin instance and register all hooks
 */
$postMinerPlugin = new PostMiner_Plugin();
$postMinerPlugin->register();

