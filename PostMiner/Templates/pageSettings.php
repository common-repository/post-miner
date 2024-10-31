<?php

if( !defined( 'POST_MINER__FILE' ) )
{
    die();
}
?>

<div class="wrap">
    <div id="icon-options-general" class="icon32">
        <br />
    </div>
    <h2>Post Miner</h2>
    
    <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=post-miner-settings.php">
    
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">
                        <label for="recommheader">Recommendations header</label>
                    </th>
                    <td>
                        <input id="recommheader" class="regular-text" type="text" value="<?php echo htmlspecialchars( $view->header ); ?>" name="recommheader" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="limit">Recommendations limit</label>
                    </th>
                    <td>
                        <input id="limit" class="small-text" type="text" value="<?php echo $view->limit; ?>" name="limit" />
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input id="submit" class="button-primary" type="submit" value="Save Changes" name="postminer-submit">
        </p>
    </form>
    
</div>