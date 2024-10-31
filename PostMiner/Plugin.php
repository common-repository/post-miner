<?php

/**
 * Entry point for the PostMiner plugin. It's a front controller for the plugin.
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 * 
 */
class PostMiner_Plugin
{
    /**
     * Instance of the Hooks class
     * @var PostMiner_Hooks
     */
    private $hooks;
    
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct() 
    {
        require_once POST_MINER__PATH . 'Hooks.php';
        
        $this->hooks = new PostMiner_Hooks( $this );
    }
    
    /**
     * Registeres plugin hooks in WordPress
     * 
     * @todo add caching to not parse the class on every instance
     * 
     * @return void
     */
    public function register()
    {
        /**
         * search for actions and filteres and register them 
         */
        foreach( get_class_methods( $this->hooks ) as $methodName )
        {
            if( ! preg_match( '/^(action|filter)([A-Z].+)/', $methodName, $m) )
            {
                continue;
            }
            
            $hookName = preg_replace('/[A-Z]/', '_$0', $m[2]);
            $hookName = substr( strtolower($hookName), 1 );
            
            switch( $m[1] )
            {
                case 'action':
                    add_action( $hookName, array( $this, '_' . $methodName ) );
                    break;
                
                case 'filter':
                    add_filter( $hookName, array( $this, '_' . $methodName ) );
                    break;
            }
        }
        
        if( method_exists( $this->hooks, 'hookActivation' ) )
        {
            register_activation_hook( POST_MINER__FILE, array( $this, '_hookActivation') );
        }

    }
    
    public function __call( $methodName, $args )
    {
        if( $methodName[0] != '_' )
        {
            return false;
        }
        
        $methodName = substr( $methodName, 1 );
        
        $view = call_user_method_array( $methodName, $this->hooks, $args );
        
        if( ! $view instanceof PostMiner_View )
        {
            return $view;
        }
        
        
        include POST_MINER__PATH . 'Templates/' . $methodName . '.php';
        
        ob_start();
        
        $return = ob_get_contents();
        
        ob_clean(); 
        
        if( PostMiner_View::$RETURN_OUTPUT )
        {
            return $return;
        }
        
        echo $return;
    }
    
    public function getHookProxy( $methodName )
    {
        return '_' . $methodName;
    }
    
}