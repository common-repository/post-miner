<?php

/**
 * PostMiner Controller
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 * 
 */
class PostMiner_Hooks
{
    protected $view;
    protected $plugin;
    
    /**
     * User's profile
     * @var PostMiner_Profile 
     */
    private $profile;
    
    public function __construct(PostMiner_Plugin $plugin) 
    {
        require_once( POST_MINER__PATH . 'View.php' );
        
        $this->view = new PostMiner_View();
        
        $this->plugin = $plugin;
    }
    /**
     * PostMiner activation hook
     * 
     * Registres the plugin, creates new tables and index contet
     * 
     * @return void
     */
    public function hookActivation()
    {
        if ( !current_user_can('activate_plugins') )
        {
            wp_die('Insufficient permissions');
        }
        
        if( version_compare( get_bloginfo( 'version' ), '3.1', '<' ) ) 
        {
            deactivate_plugins( basename( POST_MINER__FILE ) );
            
            return false;
        }
        
        require_once POST_MINER__PATH . 'Installer.php';
        
        PostMiner_Installer::install();
    }

    
    /**
     * Method indexes updated post.
     * 
     * @return void
     */
    public function actionPostUpdated()
    {
        require_once POST_MINER__PATH . 'Indexer.php';
        
        global $post;
        
        PostMiner_Indexer::indexPosts( $post->post_parent == 0 ? $post->ID : $post->post_paren );
    }
    
    /**
     * This method attaches recommendations to a post
     * 
     * @param string $content Post content
     * @return string Post content
     */
    public function filterTheContent( $content )
    {
        if( ! is_singular('post') )
        {
            return $content;
        }
        
        require_once POST_MINER__PATH . 'TermMapper.php';
        require_once POST_MINER__PATH . 'VisitsMapper.php';
        
        global $post;
        
        $termMapper = new PostMiner_TermMapper();
        
        $postId = $post->post_parent == 0 ? $post->ID : $post->post_parent;
        
        $postVector = $termMapper->getTermVectorByPostId( $postId );
        
        /**
         * don't do anything if post is not indexed
         */
        if( $postVector->getDimSize() == 0 )
        {
            return $content;
        }
        
        if( ! PostMiner_VisitsMapper::isVisited( $postId, $this->profile->getId() ) )
        {
            $this->profile->addInterest( $postVector );
            
            PostMiner_VisitsMapper::setVisited( $postId, $this->profile->getId() );
        }
        
        $this->profile->getInterest()->normalize();
        
        $ids = $termMapper->getSimilarPostIdsByProfile( $this->profile, $postId );
        
        if( empty( $ids ) )
        {
            return $content;
        }
        
        $query = new WP_Query( array(
            'post__in' => $ids
        ));
        
        $this->view->content = $content;
        
        $this->view->posts = array();
        $this->view->headerTitle = get_option("post-miner_recommendations_header");
        
        $limit = get_option('post-miner_recommendations_limit');
        
        while( $query->have_posts() )
        {
            $query->the_post();
            $this->view->posts[] = array(   'link' => get_permalink(), 
                                            'title' => get_the_title() );
            
            $limit--;
            if( $limit == 0 )
            {
                break;
            }
        }
        
        wp_reset_query();
        
        return $this->view;
    }
    
    /**
     * Adding "Post Miner" option under settings menu on admin page
     */
    public function actionAdminMenu()
    {
        add_options_page( 'Post Miner settings',
                          'Post Miner',
                          'manage_options',
                          'post-miner-settings.php',
                           array( $this->plugin, $this->plugin->getHookProxy( 'pageSettings' ) ) );
    }
    
    /**
     * Renders settings page
     * 
     * @return PostMiner_View
     */
    public function pageSettings()
    {
        if ( !current_user_can('manage_options') )
        {
            wp_die('Insufficient permissions');
        }
        
        if( isset( $_POST['postminer-submit'] ) )
        {
            update_option('post-miner_recommendations_header', $_POST['recommheader'] );
            update_option('post-miner_recommendations_limit', (int) $_POST['limit'] );
        }
        
        PostMiner_View::$RETURN_OUTPUT = false;
        
        $this->view->header = get_option('post-miner_recommendations_header');
        
        $this->view->limit = get_option('post-miner_recommendations_limit');
        
        return $this->view;
    }
    
    
    /**
     * Reads user interest
     * 
     */
    public function actionPluginsLoaded()
    {
        require_once( POST_MINER__PATH . 'Profile.php' );
        
        $uid = isset($_COOKIE[ PostMiner_Profile::COOKIE_NAME ]) ? 
                        $_COOKIE[ PostMiner_Profile::COOKIE_NAME ] : false;
        
        if( $uid )
        {
            $this->profile = PostMiner_Profile::initByUid( $uid );
        }
        
        if( ! $uid || ! $this->profile )
        {
            $this->profile = PostMiner_Profile::init();
        }
        
    }
    
}