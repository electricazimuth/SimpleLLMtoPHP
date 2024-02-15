<?php
/*
 * A way to view data - no processing in this page
 */
$time_start = microtime(true);
require("bootstrapper.inc.php");

include('templates/header.inc.php');

$do_save_to_db = false;



$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.stage = 3 AND p.provocative > 3 ORDER BY p.provocative ASC LIMIT 10"; 


$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.swearcount = 0 AND p.swearing > 0  ORDER BY p.swearing DESC LIMIT 10"; 
//p.swearcount, p.swearing, p.offensive, p.provocative
$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.swearcount > 0 ORDER BY p.swearcount ASC LIMIT 50";

$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.offensive >= 5 ORDER BY p.offensive ASC LIMIT 50";
$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.provocative >= 2 ORDER BY p.provocative DESC LIMIT 50";

$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id ORDER BY p.provocative DESC, p.offensive DESC , p.swearing DESC  LIMIT 50";
//$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.swearcount > 2 AND p.swearing > 2  ORDER BY p.swearing ASC LIMIT 10"; 

$rows = $registry->db->getRows($q);
?>
<div class="container">
<table class=" table table-striped">
<?php
if( is_array($rows) && count($rows) > 0 ){
    foreach($rows as $row){
?>
    <tr> 
        <td><?php echo $row['title'] ?> </td>
        <td><?php echo $row['artist'] ?></td>
        <td>(<?php echo $row['id'] ?>)</td>
        <td><pre><?php echo $row['lyrics'] ?></pre></td>

        <td>cnt: <?php echo $row['swearcount'] ?></td>
        <td>swe: <?php echo $row['swearing'] ?></td>
        <td>off: <?php echo $row['offensive'] ?></td>
        <td>pro: <?php echo $row['provocative'] ?></td>
    </tr>
<?php
//echo nl2br($row['lyrics']);
//$q = "SELECT id, row_id, lyrics FROM lyrics WHERE  "
// <?php echo nl2br(substr($row['lyrics'], 0 , 800));
//    <hr />
//<p> <?php echo nl2br($row['lyrics']); 

    }
}
?>
</table>
</div>
<?php
include('templates/footer.inc.php');