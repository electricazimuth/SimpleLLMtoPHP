<?php
/*
 * A way to view data - no processing in this page
 */
$time_start = microtime(true);
require("bootstrapper.inc.php");

include('templates/header.inc.php');

$do_save_to_db = false;
//section_row_id 	topic_id 	score 	


// In 2 queries
$q = "SELECT s.lyric_id, s.lyrics, s.topics, s.row_id FROM sections_topics s WHERE s.processed = 2 ORDER BY RAND() LIMIT 50"; //s.row_id DESC
$rows = $registry->db->getRows($q);
?>
<h2>Sepeate quesries</h2>
<div class="container">
<table class=" table table-striped">
<?php
foreach($rows as $row){
    $topic_q = "SELECT t.topic, st.score FROM topics t, sections_to_topics st WHERE t.topic_id != 1 AND t.topic_id = st.topic_id AND st.section_row_id = " . (int)$row['row_id'] . " ORDER BY st.score DESC, t.topic ASC LIMIT 3";
    //IN (2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,61)
    $topic_rows = $registry->db->getRows($topic_q);
    $topic_list = array();
    foreach($topic_rows as $t_row){
        $topic_list[] = $t_row['topic'] . ':'. $t_row['score'];
    }
?>
    <tr> 
        <td><?php echo $row['lyric_id'] ?> </td>    
        <td><?php echo $row['lyrics'] ?> </td>
        <td><?php echo $row['topics'] ?> </td>
        <td><?php echo implode(', ' , $topic_list ) ?> </td>
    </tr>
<?php
}

?>
</table>
</div>
<?php
$totaltime = microtime(true) - $time_start;
echo '<p>totaltime: ' . $totaltime . '</p>';




/*
$time_start = microtime(true);
$q = "SELECT s.lyric_id, s.lyrics, s.topics, t.topic, st.score FROM sections_topics s, topics t, sections_to_topics st WHERE s.processed = 2 AND t.topic_id = st.topic_id AND st.section_row_id = s.row_id ORDER BY s.row_id DESC, st.score DESC, t.topic ASC LIMIT 30"; 
//$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.swearcount > 2 AND p.swearing > 2  ORDER BY p.swearing ASC LIMIT 10"; 
$rows = $registry->db->getRows($q);
?>
<h2>One query</h2>
<div class="container">
<table class=" table table-striped">
<?php
foreach($rows as $row){
?>
    <tr> 
        <td><?php echo $row['lyric_id'] ?> </td>    
        <td><?php echo $row['lyrics'] ?> </td>
        <td><?php echo $row['topics'] ?> </td>
        <td><?php echo $row['topic'] ?> </td>
        <td><?php echo $row['score'] ?> </td>

    </tr>
<?php
}

?>
</table>
</div>
<?php
$totaltime = microtime(true) - $time_start;
echo '<p>totaltime: ' . $totaltime . '</p>';
*/
include('templates/footer.inc.php');