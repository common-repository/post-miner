<?php

/**
 * Model for interacting with visits table. 
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 */
class PostMiner_VisitsMapper
{
    /**
     * Checks has user visited a post
     * 
     * @param int $postId Wordpress post it
     * @param int $profileId post-miner profile id
     * @return boolean 
     */
    static function isVisited( $postId, $profileId )
    {
        global $wpdb;
        
        $sql = 'SELECT object_id 
                FROM `%spostminer_visits` 
                WHERE object_id = "%d"
                AND postminer_profile_id = "%d"';
        
        $ret = $wpdb->get_results( sprintf( $sql, $wpdb->prefix, $postId, $profileId ) );
        
        return sizeof( $ret ) > 0;
    }
    
    static function setVisited( $postId, $profileId )
    {
        global $wpdb;
        
        $sql = 'INSERT INTO `%spostminer_visits` (object_id, postminer_profile_id ) VALUES ("%d","%d")';
        
        $wpdb->get_results( sprintf( $sql, $wpdb->prefix, $postId, $profileId ) );
    }
}