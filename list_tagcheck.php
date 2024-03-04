<?php
/*
 * A way to view data - no processing in this page
 */
$time_start = microtime(true);
require("bootstrapper.inc.php");

include('templates/header.inc.php');

$do_save_to_db = false;
//section_row_id 	topic_id 	score 	

$q_count = "SELECT COUNT(*) as count FROM sections_topics s, topics t, sections_to_topics st WHERE s.processed = 2 AND t.topic_id = st.topic_id AND st.section_row_id = s.row_id AND t.topic LIKE 'Nonsense' AND st.score > 8 ";
$countrows = $registry->db->getRows($q_count);


$q = "SELECT s.lyrics, s.topics, t.topic, st.score FROM sections_topics s, topics t, sections_to_topics st WHERE s.processed = 2 AND t.topic_id = st.topic_id AND st.section_row_id = s.row_id ORDER BY s.row_id DESC LIMIT 100"; 

$q = "SELECT s.lyrics, s.topics, t.topic, st.score FROM sections_topics s, topics t, sections_to_topics st WHERE s.processed = 2 AND t.topic_id = st.topic_id AND st.section_row_id = s.row_id AND t.topic LIKE 'Nonsense' AND st.score > 8 ORDER BY RAND() DESC LIMIT 100"; 
//$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.* FROM lyrics_hot100 l, processing p WHERE p.exclude = 0 AND p.lyric_id = l.id AND p.swearcount > 2 AND p.swearing > 2  ORDER BY p.swearing ASC LIMIT 10"; 

$rows = $registry->db->getRows($q);


?>
<div class="container">
    <p>Total <?php echo $countrows[0]['count'] ?> rows </p>
<table class=" table table-striped">
<?php
foreach($rows as $row){
?>
    <tr> 
        <td><?php echo $row['lyrics'] ?> </td>
        <td><?php echo $row['topics'] ?> </td>
        <td><?php echo $row['topic'] ?> </td>
        <td><?php echo $row['score'] ?> </td>

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