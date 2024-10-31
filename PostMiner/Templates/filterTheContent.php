<?php

if( !defined( 'POST_MINER__FILE' ) )
{
    die();
}

echo $view->content;

?>
<div id="postMiner">
    <h5><?php echo $view->headerTitle; ?></h5>
    <ul>
        <?php
        
        foreach( $view->posts as $post )
        {
            printf('<li><a href="%s">%s</a></li>', $post['link'], $post['title'] );  
        }
        
        ?>
    </ul>
</div>