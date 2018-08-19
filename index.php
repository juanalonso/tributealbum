<?php 


include("config/config.php");


//http://altorouter.com/
require 'vendor/autoload.php';


$accessToken = getToken($clientId, $clientSecret);
$albumTitle = "crisis en";


//----------------------------------------------------------
$router = new AltoRouter();
//$router->setBasePath('/alto-app/');

$router->map( 'GET', '/', 'renderHome', 'home' );
$router->map( 'GET', '/original/[a:id]', 'renderOriginal', 'original' );

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
    call_user_func_array( $match['target'], $match['params'] ); 
} else {
    // no route was matched
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}
//----------------------------------------------------------




function renderHome()  {

global $albumTitle, $accessToken;
$albumData = getAlbumData($albumTitle, $accessToken);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>AlterAlbum</title>
    </head>
    <body>
        <h1>AlterAlbum</h1>
        <h2><?= $albumTitle ?></h2>

<?php
foreach ($albumData["albums"]["items"] as $album) {

    $artistArray = array();
    foreach ($album["artists"] as $artist) {
        $artistArray[] =  $artist["name"];
    }

    echo "<a href='/original/" . $album["id"] . "'>" . $album["name"] ."</a>\n";
    echo implode(", ", $artistArray) . "\n\n";
    echo "<img src='" . $album["images"]["1"]["url"] . "' width='300' height='300'/>\n";
}
?>

    </body>
</html>

<?php

}


function renderOriginal($albumId)  {

global $albumTitle, $accessToken;

//$albumData = getAlbumData($albumTitle, $accessToken);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>AlterAlbum</title>
    </head>
    <body>
        <h1>AlterAlbum</h1>
        <h2><?= $albumTitle . " ($albumId)" ?></h2>
    </body>
</html>

<?php
}




function getToken($clientId, $clientSecret){

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            'https://accounts.spotify.com/api/token' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_POST,           1 );
    curl_setopt($ch, CURLOPT_POSTFIELDS,     'grant_type=client_credentials' ); 
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Authorization: Basic '.base64_encode($clientId.':'.$clientSecret))); 

    $result=curl_exec($ch);
    $json = json_decode($result, true);

    //print_r($json);

    return $json['access_token'];

}



function getAlbumData($albumTitle, $accessToken) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/search?q='.urlencode("album:\"$albumTitle\"").'&type=album');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_HTTPHEADER,
                  array('Accept: application/json', 
                        'Content-Type: application/json', 
                        'Authorization: Bearer '.$accessToken)); 

    $result=curl_exec($ch);
    $json = json_decode($result, true);

    //print_r($json);

    return $json;

}