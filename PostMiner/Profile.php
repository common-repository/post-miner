<?php

require_once( POST_MINER__PATH . 'TermVector.php' );

/**
 * Object representation of user interest
 *  
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 * 
 */
class PostMiner_Profile
{
    /**
     * Cookie name
     * @var string
     */
    const COOKIE_NAME = 'wp-post-miner';
    
    /**
     * profile id
     * @var int
     */
    private $id;
    
    /**
     * unique identifier which goes to the cookie
     * @var string
     */
    private $uid;
    
    /**
     * Interest vector
     * @var PostMiner_TermVector
     */
    private $interest;
    
    public function __construct( PostMiner_TermVector $interest, $id, $uid )
    {
        $this->id = $id;
        $this->uid = $uid;
        $this->interest = $interest;
        
        setcookie( self::COOKIE_NAME, $uid, time() + 31536000, SITECOOKIEPATH);
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getInterest()
    {
        return $this->interest;
    }
    
    public function addInterest( PostMiner_TermVector $vector )
    {
        if( $vector->getDimSize() == 0 )
        {
            return false;
        }
        
        /**
         * some interest might be temporary. This will gradually fade it out.
         */
        //$this->interest->mul( 0.99 );
        
        /**
         * add new interest
         */
        $this->interest->sum( $vector );
        
        $this->update();
    }
    
    public function update()
    {
        global $wpdb;
        
        /**
         * remove old interest vector
         */
        $sql = 'DELETE FROM %spostminer_profile_interest WHERE profile_id = "%d"';
        
        $wpdb->get_results( sprintf( $sql, $wpdb->prefix, $this->id ) );
        
        /**
         * create new interest vector
         */
        $sql = "INSERT INTO %spostminer_profile_interest 
                (profile_id, postminer_term_id, weight) VALUES('%s')";
        
        $insert = array();
        
        foreach( $this->interest->getValues() as $termId => $value )
        {
            $insert[] = sprintf("%s','%s','%s", $this->id, $termId, $value );
        }
        
        $wpdb->get_results( sprintf( $sql, $wpdb->prefix, implode("'),('", $insert ) ) );
        
        $sql = 'UPDATE %spostminer_profiles 
                SET updated = now()
                WHERE id = "%d" ';
        
        $wpdb->get_results( sprintf( $sql, $wpdb->prefix, $this->id ) );
    }
    
    public function insert()
    {
        global $wpdb;
        
        $sql = 'INSERT INTO %spostminer_profiles (id, uid, created, updated) 
                VALUES (null, "%s", now(), now() )';
        
        $wpdb->get_results( sprintf( $sql, $wpdb->prefix, $this->uid ) );
        
        $this->id = $wpdb->insert_id;
    }
    
    static function initByUid( $uid )
    {
        global $wpdb;
        
        $uid = preg_replace( '/[^a-z0-9]/', '', $uid );
        
        
        $sql = 'SELECT p.id, i.postminer_term_id termid, i.weight 
                FROM %spostminer_profiles p
                JOIN wp_postminer_profile_interest i ON i.profile_id = p.id
                WHERE p.uid = "%s"';
        
        $r = $wpdb->get_results( sprintf( $sql, $wpdb->prefix, $uid ) );
        
        if( sizeof( $r ) == 0 )
        {
            return false;
        }
        
        $interest = array();
        foreach( $r as $row )
        {
            $interest[ $row->termid ] = $row->weight;
            $id = $row->id;
        }
        
        return new PostMiner_Profile( new PostMiner_TermVector( $interest ), $id, $uid );;
    }
    
    static function init()
    {
        $chars = "1234567890qwertyuiopasdfghjklzxcvbnm";
        $charsLen = strlen( $chars ) - 1;
        
        $uid = '';
        
        for( $i = 0 ; $i < 32 ; $i++ )
        {
            $uid .= $chars[ rand(0, $charsLen ) ];
        }
        
        $profile = new PostMiner_Profile( new PostMiner_TermVector( array() ), 0, $uid );
        $profile->insert();
        
        return $profile;
    }
}