<!doctype html>
<html lang="en">
 <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Van Zussi">
    <meta name="generator" content="azimuth.web">
    <title>Admin | Lyrics</title>

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">

    <link href="css/admin.css" rel="stylesheet">
 </head>
 <body class="<?php echo $mode ?>">

 <?php
 $nav = array(
    'Home' => '?mode=home',
    'One' => '?mode=one',
    'Two' => '?mode=two',
 );
 
 ?>

<header class="masthead mb-auto container d-flex w-100 h-100 p-3 mx-auto flex-column">
    <div class="inner">
      <h3 class="masthead-brand">

	      <a href="/"><img src="assets/azimuth.svg" width="60" alt="azimuth" /></a>

      </h3>
      <nav class="nav nav-masthead justify-content-center">
	    <?php
		if( isset($nav) && is_array($nav) && count($nav)):
			foreach($nav as $title => $url ){

				$thisClass = '';
                /*
				if( $url == '/' && $urlArray['thisSection'] == '/home' ){
					$thisClass .= 'active';
				}elseif( strpos($url, $urlArray['thisSection']) !== false ){
					$thisClass .= 'active';
				}		
				*/
				echo '<a class="nav-link ' . $thisClass. '" href="' . $url . '">' . $title . '</a>';
			}
		endif;    
		?>
      </nav>
    </div>
</header>

<div class="container">