<?php

if( ! isset( $_SERVER['SHELL'] ) )
{
    die();
}



// define( 'SHORTINIT', true );

//$path = preg_replace( '/\/wp-content\/.*/', '', realpath(__FILE__) );

// define( 'WP_PATH', $path );

// require_once( $path . '/wp-load.php' );
// require_once( $path . '/wp-includes/formatting.php' );

$path = preg_replace( '/[^\/]+\.php$/', '', realpath(__FILE__)  );


defined('POST_MINER__PATH') || 
    define( 'POST_MINER__PATH', $path . 'PostMiner/' );

