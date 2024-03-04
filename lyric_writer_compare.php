<?php
/*
 */

set_time_limit(0);

require("bootstrapper.inc.php");

$total = 200;


//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
//$q = "SELECT DISTINCT(topics) as gen_topics FROM generation_tests LIMIT " . $total;
$q = "SELECT * FROM generation_tests ORDER BY topics ASC, model ASC, prompt_type DESC LIMIT " . $total;

// [model] [type] [topic] => lyrics

$rows = $registry->db->getRows($q);

$data = array();
$topics = array();
/*
echo '<table>';
echo '<tr>' 
        '<td> model </td> <td> type </td> <td> topic </td> ' */

foreach($rows as $row){

    $data[ $row['topics'] ][ $row['model'] ][ $row['prompt_type'] ] = $row['lyrics'];
    if(!in_array($row['topics'], $topics )){
        $topics[] =  $row['topics'];
    }

}
?>
<!doctype html>
<html lang="en">
 <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Van Zussi">
    <meta name="generator" content="azimuth.web">
    <title> Lyrics Gen Check</title>

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
 </head>
 <body class="">


<?php

foreach( $topics as $topic){
    
    echo '<table class="table table-striped " style="break-inside: avoid-page;">';
    echo '<tr><td colspan=3><h2 class="mt-4">' . $topic . '</h2><td></tr>';
    echo '<tr><td>Model</td> <td>Simpleton</td> <td>Lyric Writer</td> <td>Beatles</td></tr> ';

    foreach( $data[$topic] as $_model => $_type  ){
        //foreach( $type as $_type => $lyrics){
            echo '<tr><td>'. str_replace('koboldcpp/','',$_model).'</td> <td><pre>'.$_type['simpleton'].'</pre></td> <td><pre>'.$_type['lyric_writer'].'</pre></td> <td><pre>'.$_type['beatles_writer'].'</pre></td> </tr> '; 
        //}
          
    }

/*
    foreach( $data[$topic] as $_model => $type  ){
        foreach( $type as $_type => $lyrics){
            echo '<tr><td>'.$_model.'</td> <td>'.$_type.'</td> <td><pre>'.$lyrics.'</pre></td></tr> '; 
        }
          
    }
*/

    echo '</table>';

}


/*
echo '<pre>';
var_dump( $file_to_word );
echo '</pre>';

echo json_encode($loginfo);
echo ' done ' . PHP_EOL ;
*/