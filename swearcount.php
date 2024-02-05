<?php
$time_start = microtime(true);
require("bootstrapper.inc.php");


include('templates/header.inc.php');

//$q = "SELECT * FROM hot100_uniq ORDER BY weeks DESC LIMIT 0,1";
//$q = "SELECT p.*, l.lyrics, l.title, l.artist FROM lyrics l, processing p WHERE p.lyric_id = l.id ORDER BY p.pri_key ASC LIMIT 1,3";
//$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.artist LIKE 'Eminem' ORDER BY RAND() LIMIT 1";
//$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.row_id = 2332"; //19693";// 2332"; 533 622

$q = "SELECT p.*, l.lyrics FROM lyrics_hot100 l, processing p WHERE p.stage = 0 AND p.lyric_id = l.id ORDER BY p.pri_key ASC";// LIMIT 0,20500";
// RESET : UPDATE processing SET swearcount = 0 , stage = 0 
// INFO: SELECT p.*, l.title, l.artist FROM lyrics_hot100 l, processing p WHERE p.stage = 1 AND p.lyric_id = l.id ORDER BY p.swearcount DESC
//1202  2332 1182
// l.row_id ASC LIMIT 1,1";

$rows = $registry->db->getRows($q);
$complete = 0;
if( is_array($rows) && count($rows) > 0 ){
    foreach($rows as $row){
        $swear_count = Utils::GetSwearCount($row['lyrics']);
        //echo '<p>' . $row['pri_key'] . ':' . $swear_count['score'] . '</p>';
        //if( isset($swear_count['score']) && $swear_count['score'] > 0 ){
            $q = "UPDATE processing SET swearcount = " . (int)$swear_count['score'] . " , stage = 1 WHERE pri_key = " . (int)$row['pri_key'];
            $registry->db->sendQuery($q);
            $complete++;
        //}


       // $reply = $registry->llm->Generate($row['lyrics']);

    }
}


$end_time = microtime(true) - $time_start;
?>
    <h3>Swear count</h3>
    <p>completed <?php echo $complete ?> in <?php echo $end_time ?> sec</p>
<?php
include('templates/footer.inc.php');
// 403031 1651690 75244