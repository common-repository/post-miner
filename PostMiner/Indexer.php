<?php

/**
 * PostMiner_Indexer indexes Wordpress posts. It converts categories, tags and
 * title into a post vector and saves it in the postminer_term_relationships table.
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 */
class PostMiner_Indexer
{
    /**
     * SQL for getting all wordpress posts with tags and categories
     * @var string
     */
    static private $SQL_POSTS = "SELECT p.id, p.post_title, tt.taxonomy, t.name
                            FROM %sposts p
                            JOIN %sterm_relationships rs ON rs.object_id = p.id
                            JOIN %sterm_taxonomy tt ON (tt.term_taxonomy_id = rs.term_taxonomy_id)
                            JOIN %sterms t ON t.term_id = tt.term_id
                            LEFT JOIN wp_postminer_term_relationships ptr ON ptr.object_id = p.id
                            where tt.taxonomy IN ( 'category', 'post_tag' )
                            and p.post_parent = 0
                            and p.post_status = 'publish'
                            and ptr.object_id is null";
    
    /**
     * It returns post data for a particular post id
     * @var string 
     */
    static private $SQL_POSTS_BY_ID = "SELECT p.id, p.post_title, tt.taxonomy, t.name
                            FROM %sposts p
                            JOIN %sterm_relationships rs ON rs.object_id = p.id
                            JOIN %sterm_taxonomy tt ON (tt.term_taxonomy_id = rs.term_taxonomy_id)
                            JOIN %sterms t ON t.term_id = tt.term_id
                            where tt.taxonomy IN ( 'category', 'post_tag' )
                            and p.post_parent = 0
                            and p.post_status = 'publish'
                            and p.id = %d";
    
    /**
     * SQL for getting PostMiner terms for a particular ids
     * @var string 
     */
    static private $SQL_GET_TERMS = "SELECT id, value FROM %spostminer_terms WHERE value IN( '%s' )";
    
    /**
     * SQL for inserting new terms
     * @var string
     */
    static private $SQL_INSERT_TERMS = "INSERT INTO %spostminer_terms (value) VALUES ('%s')";
    
    /**
     * SQL for inserting a post vector
     * @var string
     */
    static private $SQL_INSERT_TERM_RELATION = "INSERT INTO %spostminer_term_relationships (object_id, postminer_term_id, weight) VALUES('%s')";

    /**
     * SQL for removing a post vector
     * @var string 
     */
    static private $SQL_DELETE_TERM_RELATION = "DELETE FROM %spostminer_term_relationships WHERE object_id = '%d'";
    
    /**
     * Returns an array with wordpress posts in a convenient for the indexer form
     * 
     * @param int $indexPostId postId to be indexed. If it's false all not indexed post ids will be returned
     * @return Array of posts to be indexed 
     */
    static private function getPostsToIndex( $indexPostId = false )
    {
        global $wpdb;
        
        $posts = array();
        
        if( $indexPostId )
        {
            $dbRet = $wpdb->get_results( sprintf( self::$SQL_POSTS_BY_ID, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $indexPostId ) );
        }
        else
        {
            $dbRet = $wpdb->get_results( sprintf( self::$SQL_POSTS, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix ) );
        }
        
        foreach( $dbRet as $row )
        {
            $posts[ $row->id ][ $row->taxonomy ][] = $row->name;
            $posts[ $row->id ][ 'title' ] = $row->post_title;
        }
        
        return $posts;
    }
    
    /**
     * This method calculates a post vector from a post array returned by the PostMiner_Indexer::getPostsToIndex()
     * 
     * @param PostMiner_Engine $postMiner an instance of a PostMiner_Engine
     * @param Array $data raw post data
     * @return PostMiner_TermVector 
     */
    static private function getPostVector( PostMiner_Engine $postMiner, $data )
    {
        /**
         * get term vector from the post title
         */
        $postVector = $postMiner->createTermVector( $data['title'], 1 );
            
        /**
         * get term vector from the post category and add it to the $postVector
         */
        if( isset( $data['categoty'] ) )
        {
            $categories = implode( ' ', $data['categoty'] );
            $categoryVector = $postMiner->createTermVector( $categories, 1 );
            $postVector = $postVector->sum( $categoryVector );
        }

        /**
         * get term vector from the post tags and add it to the $postVector
         */
        if( isset( $data['post_tag'] ) )
        {
            $tags = implode( ' ', $data['post_tag'] );
            $tagsVector = $postMiner->createTermVector( $tags, 1 );       
            $postVector = $postVector->sum( $tagsVector );
        }

        /**
         * normalize vector
         */
        $postVector->normalize();
        
        return $postVector;
    }
    
    /**
     * Index posts. 
     * @global type $wpdb
     * @param type $indexPostId
     * @return type 
     */
    static function indexPosts( $indexPostId = false )
    {  
        require_once POST_MINER__PATH . 'Engine.php';
        
        global $wpdb;
        
        $postMiner = new PostMiner_Engine();
        
        $posts = self::getPostsToIndex( $indexPostId );
        
        if( empty( $posts ) )
        {
            return false;
        }

        $termsId = array();
        
        foreach( $posts as $postId => $data )
        {
            $postVector = self::getPostVector( $postMiner, $data );
            
            $toLoad = array();
            
            foreach( $postVector->getDimensions() as $dim )
            {
                if( isset( $temsId[ $dim ] ) )
                {
                    continue;
                }
                
                $toLoad[ $dim ] = addslashes( $dim );
            }
            
            
            /**
             * Load required terms
             */
            foreach( $wpdb->get_results( sprintf( self::$SQL_GET_TERMS, $wpdb->prefix, implode("','", $toLoad ) ) ) as $row )
            {
                $termsId[ $row->value ] = $row->id;
                
                unset( $toLoad[ $row->value ] );
            }
            
            /**
             * Insert missing terms
             */
            if( !empty($toLoad))
            {
                error_log( sprintf( self::$SQL_INSERT_TERMS, $wpdb->prefix, implode("'),('", $toLoad) ));
                $wpdb->get_results( sprintf( self::$SQL_INSERT_TERMS, $wpdb->prefix, implode("'),('", $toLoad) ) );
                foreach( $wpdb->get_results( sprintf( self::$SQL_GET_TERMS, $wpdb->prefix, implode("','", $toLoad ) ) ) as $row )
                {
                    $termsId[ $row->value ] = $row->id;
                }
            }
            
            /**
             * associate terms with post
             */
            $insert = array();
            foreach( $postVector->getValues() as $dim => $value )
            {
                $insert[] = sprintf("%s','%s','%s", $postId, $termsId[ $dim ], $value );
            }
            
            if( ! empty( $insert) )
            {
                /**
                 * remove old vector
                 */
                if( $indexPostId )
                {
                    $wpdb->get_results( sprintf( self::$SQL_DELETE_TERM_RELATION, $wpdb->prefix, $indexPostId ) );
                }
                
                $wpdb->get_results( sprintf( self::$SQL_INSERT_TERM_RELATION, $wpdb->prefix, implode("'),('", $insert ) ) );
            }
        }
        
    }
}