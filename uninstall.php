<?php

if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
{
    exit();
}

if ( !current_user_can('delete_plugins') )
{
    wp_die('Insufficient permissions');
}

defined('POST_MINER__PATH') || 
    define( 'POST_MINER__PATH', plugin_dir_path( __FILE__ ) . '/PostMiner/' );


require_once POST_MINER__PATH . 'Installer.php';

PostMiner_Installer::uninstall();
