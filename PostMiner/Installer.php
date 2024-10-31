<?php

/**
 * Plugin installer. This class is a container for install and uninstall code.
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 */
class PostMiner_Installer
{
    
    /**
     * SQL statement for creating terms table
     * @var string
     */
    static private $SQL_TERMS = "CREATE TABLE IF NOT EXISTS `%s_terms` (
                                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `value` varchar(32) NOT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `value` (`value`)
                                ) ENGINE=MyISAM AUTO_INCREMENT=1";
    
    
    /**
     * SQL statement for creating term_relationships table
     * @var string
     */
    static private $SQL_TERMS_RELATION = "CREATE TABLE IF NOT EXISTS `%s_term_relationships` (
                                  `object_id` bigint(20) unsigned NOT NULL,
                                  `postminer_term_id` int(10) unsigned NOT NULL,
                                  `weight` double NOT NULL,
                                  KEY `object_id` (`object_id`,`postminer_term_id`)
                                ) ENGINE=MyISAM";

    
    /**
     * SQL statement for creating profiles table
     * @var string
     */
    static private $SQL_PROFILE = "CREATE TABLE IF NOT EXISTS `%s_profiles` (
                                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `uid` varchar(32) NOT NULL,
                                  `created` datetime NOT NULL,
                                  `updated` datetime NOT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `uid` (`uid`)
                                ) ENGINE=MyISAM  ;";
    
    /**
     * SQL statement for creating profile interest table
     * @var string
     */
    static private $SQL_PROFILE_INTEREST = "CREATE TABLE IF NOT EXISTS `%s_profile_interest` (
                                  `profile_id` bigint(20) unsigned NOT NULL,
                                  `postminer_term_id` int(10) unsigned NOT NULL,
                                  `weight` double NOT NULL,
                                  KEY `object_id` (`profile_id`,`postminer_term_id`)
                                ) ENGINE=MyISAM ;";
    
    /**
     * SQL statement for creating visits table
     * @var string
     */
    static private $SQL_VISITED = "CREATE TABLE IF NOT EXISTS `%s_visits` (
                                  `object_id` bigint(20) unsigned NOT NULL,
                                  `postminer_profile_id` int(10) unsigned NOT NULL,
                                  KEY `postminer_profile_id` (`postminer_profile_id`)
                                ) ENGINE=MyISAM";
    
    /**
     * SQL statement for dropping terms table
     * @var string 
     */
    static private $SQL_DROP_TERMS = "DROP TABLE `%s_terms`";
    
    /**
     * SQL statement for dropping terms relationship table
     * @var string
     */
    static private $SQL_DROP_TERMS_RELATION = "DROP TABLE `%s_term_relationships`";
    
    /**
     * SQL statement for dropping profile table
     * @var string
     */
    static private $SQL_DROP_PROFILE = "DROP TABLE `%s_profiles`";
    
    /**
     * SQL statement for dropping profile interest table
     * @var string
     */
    static private $SQL_DROP_PROFILE_INTEREST = "DROP TABLE `%s_profile_interest`";
    
    /**
     * SQL statement for dropping visits table
     * @var string
     */
    static private $SQL_DROP_VISITED = "DROP TABLE `%s_visits`";
    
    /**
     * SQL statement for deleting terms relation content
     * @var string
     */
    static private $SQL_DELETE_TERMS_RELATION = 'DELETE FROM `%s_term_relationships`';

    /**
     * SQL statement for deleting terms
     * @var string
     */
    static private $SQL_DELETE_TERMS = 'DELETE FROM `%s_terms`';

    
    /**
     * Plugin installer. Creates tables and index all posts.
     *  
     * @return void
     */
    static function install()
    {
        require_once( POST_MINER__PATH . 'Indexer.php' );
        
        global $wpdb;
        
        $prefix = $wpdb->prefix . 'postminer';
        
        $wpdb->get_results( sprintf( self::$SQL_TERMS, $prefix ) );
        $wpdb->get_results( sprintf( self::$SQL_TERMS_RELATION, $prefix ) );
        
        $wpdb->get_results( sprintf( self::$SQL_PROFILE, $prefix ) );
        $wpdb->get_results( sprintf( self::$SQL_PROFILE_INTEREST, $prefix ) );
        
        $wpdb->get_results( sprintf( self::$SQL_VISITED, $prefix ) );
        
        
        if( get_option("post-miner_db_version") == '1.0.0' )
        {
            update_option("post-miner_db_version", '1.0.1');
            
            update_option("post-miner_recommendations_header", "Recommended for you:");
            
            $wpdb->get_results( sprintf( self::$SQL_DELETE_TERMS_RELATION, $prefix ) );
            
            $wpdb->get_results( sprintf( self::$SQL_DELETE_TERMS, $prefix ) );
        }
        else
        {
            add_option("post-miner_db_version", '1.0.0');
            
            add_option("post-miner_recommendations_header", "Recommended for you:");
        
            add_option("post-miner_recommendations_limit", 5);
        }
        
        PostMiner_Indexer::indexPosts();
        
    }
    
    /**
     * Plugin uninstaller.
     * 
     * @return void
     */
    static function uninstall()
    {
        global $wpdb;
        
        $prefix = $wpdb->prefix . 'postminer';
        
        $wpdb->get_results( sprintf( self::$SQL_DROP_TERMS, $prefix ) );
        $wpdb->get_results( sprintf( self::$SQL_DROP_TERMS_RELATION, $prefix ) );
        
        $wpdb->get_results( sprintf( self::$SQL_DROP_PROFILE, $prefix ) );
        $wpdb->get_results( sprintf( self::$SQL_DROP_PROFILE_INTEREST, $prefix ) );
        
        $wpdb->get_results( sprintf( self::$SQL_DROP_VISITED, $prefix ) );
        
        delete_option("post-miner_db_version");
        delete_option("post-miner_recommendations_header");
        delete_option("post-miner_recommendations_limit");
    }
}