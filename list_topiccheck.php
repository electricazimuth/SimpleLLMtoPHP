<?php
/*
 * A way to view data - no processing in this page
 */
$time_start = microtime(true);
require("bootstrapper.inc.php");

include('templates/header.inc.php');

$do_save_to_db = false;



$q = "SELECT * FROM `sections_topics` WHERE `processed` = 1 ORDER BY `row_id` DESC LIMIT 500"; 
//$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.swearcount > 2 AND p.swearing > 2  ORDER BY p.swearing ASC LIMIT 10"; 

$rows = $registry->db->getRows($q);
$topics = array();
if( is_array($rows) && count($rows) > 0 ){
    foreach($rows as $row){
        $lines = explode("\n", $row['topics']);
        foreach( $lines as $line){
            $matches = array();
            preg_match('/([a-z ]+)/i', $line, $matches);
            if( is_array($matches) && count($matches) ){
                if( array_key_exists($matches[1], $topics) ){
                    $topics[ $matches[1] ]++;
                }else{
                    $topics[ $matches[1] ] = 1;
                }
            }

        }
        
    }
}

arsort($topics);
?>
<div class="container">
<table class=" table table-striped">
<?php
foreach($topics as $k => $v){
?>
    <tr> 
        <td><?php echo $k . ' : ' .  $v ?> </td>
    </tr>
<?php
//echo nl2br($row['lyrics']);
//$q = "SELECT id, row_id, lyrics FROM lyrics WHERE  "
// <?php echo nl2br(substr($row['lyrics'], 0 , 800));
//    <hr />
//<p> <?php echo nl2br($row['lyrics']); 

}

?>
</table>
</div>
<?php
include('templates/footer.inc.php');