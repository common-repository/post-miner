<?php

require_once POST_MINER__PATH . 'TermVector.php';

/**
 * Model for interaction with the database. 
 * 
 * @todo this class should be reviewd as it contains some logic which should be in the Engine class.
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 * 
 */
class PostMiner_TermMapper
{

    private $wpdb;

    
    public function __construct()
    {
        global $wpdb;
        
        $this->wpdb = $wpdb;
    }
    
    public function getTermVectorByPostId( $postId )
    {
        $sql = 'SELECT pt.id, ptr.weight
                FROM %spostminer_term_relationships ptr
                JOIN %spostminer_terms pt ON pt.id = ptr.postminer_term_id
                WHERE ptr.object_id = %d';
        
        $vector = array();
        
        foreach( $this->wpdb->get_results( sprintf( $sql, $this->wpdb->prefix, $this->wpdb->prefix, $postId ) ) as $row )
        {
            $vector[ $row->id ] = $row->weight;
        }

        return new PostMiner_TermVector( $vector );
    }
    /**

     */
    
    public function getSimilarPostIdsByProfile( PostMiner_Profile $profile, $ignorePostId  )
    {
        $sql = ' SELECT u.object_id, if( pv.object_id is null, sum(u.weight), sum(u.weight) -1000) coorelation, pv.object_id visited_id 
                FROM
                (
                    SELECT ptr.object_id, (ptr.weight * pi.weight) weight
                    FROM `%spostminer_term_relationships` ptr
                    JOIN `%spostminer_terms` pt ON pt.id = ptr.postminer_term_id
                    JOIN `%spostminer_profile_interest` pi ON (pi.postminer_term_id = ptr.postminer_term_id)
                    WHERE profile_id = %d
                ) as u 
                LEFT JOIN `%spostminer_visits` pv ON (pv.object_id = u.object_id) 
                WHERE u.object_id != "1738" group by u.object_id order by coorelation desc limit 10';
    
        
        $posts = array();
        
        foreach( $this->wpdb->get_results( sprintf( $sql, $this->wpdb->prefix, 
                                                          $this->wpdb->prefix, 
                                                          $this->wpdb->prefix,
                                                          $profile->getId(),
                                                          $this->wpdb->prefix ) ) as $row )
        {
            $posts[] = $row->object_id;
        }
                
        return $posts;
        
    }
    
    public function getSimilarPostIds( PostMiner_TermVector $vector, $profileId, $ignorePostId = false )
    {
        $_sql = 'SELECT object_id, (weight * %s) weight
                 FROM %spostminer_term_relationships
                 WHERE postminer_term_id = %d';

        $subSelect = array();
        $ids = array();
        foreach( $vector->getValues() as $termId => $weight )
        {
            $ids[] = $termId;
            $subSelect[] = sprintf( $_sql, $weight, $this->wpdb->prefix, $termId );
        }
 
        $sql = 'SELECT u.object_id, if( pv.object_id is null, sum(u.weight), sum(u.weight) -1000) coorelation, pv.object_id visited_id FROM( ';
        //$sql.= implode( ' UNION ', $subSelect );
        $sql.= ') as u ';
        
	$sql.= 'LEFT JOIN `%spostminer_visits` pv ON (pv.object_id = u.object_id)';

        if( $ignorePostId )
        {
            $sql.= sprintf(' WHERE u.object_id != "%d" ', $ignorePostId );
        }
        else
        {
            $sql.= ' WHERE 1=1';
        }
        
	$sql.= 'group by u.object_id order by coorelation desc limit 10';
        

        $posts = array();
        foreach( $this->wpdb->get_results( sprintf( $sql, $this->wpdb->prefix, $this->wpdb->prefix, $profileId ) ) as $row )
        {
            $posts[] = $row->object_id;
        }
                
        return $posts;
    }
}
