<?php
/*
Plugin Name: Mass Remove Links
Plugin URI: http://yourls.org/
Description: Remove several (or all) links.
Version: 1.0
Author: Ozh
Author URI: http://ozh.org/
*/

yourls_add_action( 'plugins_loaded', 'ozh_yourls_linkmr_add_page' );
function ozh_yourls_linkmr_add_page() {
        yourls_register_plugin_page( 'ozh_lmr', 'Link Mass Remove', 'ozh_yourls_linkmr_do_page' );
}

// Display admin page
function ozh_yourls_linkmr_do_page() {
        if( isset( $_POST['action'] ) && $_POST['action'] == 'link_mass_remove' ) {
                ozh_yourls_linkmr_process();
        } else {
                ozh_yourls_linkmr_form();
        }
}

// Display form
function ozh_yourls_linkmr_form() {
        $nonce = yourls_create_nonce('link_mass_remove');
        echo <<<HTML
<h2>Link Mass Remove</h2>
<p>Remove the following links:</p>
<form method="post">
<input type="hidden" name="action" value="link_mass_remove" />
<input type="hidden" name="nonce" value="$nonce" />

<p><label for="radio_date">
<input type="radio" name="what" id="radio_date" value="date"/>All links created on date
</label>
<input type="text" name="date" /> (mm/dd/yyyy)
</p>
<p><label for="radio_daterange">
<input type="radio" name="what" id="radio_daterange" value="daterange"/>All links created between
</label>
<input type="text" name="date1" /> and <input type="text" name="date2" /> (mm/dd/yyyy)
</p>
<p><label for="radio_ip">
<input type="radio" name="what" id="radio_ip" value="ip"/>All links created by IP
</label>
<input type="text" name="ip" />
</p>
<p><label for="radio_url">
<input type="radio" name="what" id="radio_url" value="url"/>All links pointing to a long URL containing
</label>
<input type="text" name="url" /> (case sensitive)
</p>
<p><label for="radio_all">
<input type="radio" name="what" id="radio_all" value="all"/>All links. All.
</label>
</p>
<p><label for="check_test"><input type="checkbox" id="check_test" name="test" value="test" /> Display results, do not delete. This is a test.</label></p>
<p><input type="submit" value="Delete" /> (no undo!)</p>
</form>
<script>
function select_radio(el){
$(el).parent().find(':radio').click();
}
$('input:text')
.click(function(){select_radio($(this))})
.focus(function(){select_radio($(this))})
.change(function(){select_radio($(this))});
</script>
HTML;
}

function ozh_yourls_linkmr_process() {
        // Check nonce
        yourls_verify_nonce( 'link_mass_remove' );
        
        $where = '';
        
        switch( $_POST['what'] ) {
                case 'all':
                        $where = '1=1';
                        break;
                        
                case 'date':
                        $date = yourls_sanitize_date_for_sql( $_POST['date'] );
                        $where = "`timestamp` BETWEEN '$date 00:00:00' and '$date 23:59:59'";
                        break;
                        
                case 'daterange':
                        $date1 = yourls_sanitize_date_for_sql( $_POST['date1'] );
                        $date2 = yourls_sanitize_date_for_sql( $_POST['date2'] );
                        $where = "`timestamp` BETWEEN '$date1 00:00:00' and '$date2 23:59:59'";
                        break;
                        
                case 'ip':
                        $ip = yourls_escape( $_POST['ip'] );
                        $where = "`ip` ='$ip'";
                        break;
                        
                case 'url':
                        $url = yourls_escape( $_POST['url'] );
                        $where = "`url` LIKE '%$url%'";
                        break;
                        
                default:
                        echo 'Not implemented';
                        return;
        }
        
        global $ydb;
        
        $action = ( isset( $_POST['test'] ) && $_POST['test'] == 'test' ) ? 'SELECT' : 'DELETE' ;
        $select = ( $action == 'SELECT' ) ? '`keyword`,`url`' : '';

        $table = YOURLS_DB_TABLE_URL;
        $query = $ydb->get_results("$action $select FROM `$table` WHERE $where");
        
        if( $action == 'SELECT' ) {
                if( !$query ) {
                        echo 'No link found.';
                        return;
                } else {
                        echo '<p>'.count( $query ).' found:</p>';
                        echo '<ul>';
                        foreach( $query as $link ) {
                                $short = $link->keyword;
                                $url = $link->url;
                                echo "<li>$short: <a href='$url'>$url</a></li>\n";
                        }
                        echo '</ul>';
                        unset( $_POST['test'] );
                        echo '<form method="post">';
                        foreach( $_POST as $k=>$v ) {
                                if( $v )
                                        echo "<input type='hidden' name='$k' value='$v' />";
                        }
                        echo '<input type="submit" value="OK. Delete" /></form>';
                }
        } else {
                echo "Link(s) deleted.";
        }
}
