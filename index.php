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
$router->map( 'GET', '/album/[a:id]', 'renderAlbum', 'album' );

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
$albumList = getAlbumList($albumTitle, $accessToken);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>TributeAlbum</title>
    </head>
    <body>
        <h1>TributeAlbum</h1>
        <h2><?= $albumTitle ?></h2>

<?php
foreach ($albumList["albums"]["items"] as $album) {

    $artistArray = array();
    foreach ($album["artists"] as $artist) {
        $artistArray[] =  $artist["name"];
    }

    echo "<img src='" . $album["images"]["1"]["url"] . "' width='300' height='300'/><br/>\n";
    echo "<a href='/album/" . $album["id"] . "'>" . $album["name"] ."</a><br/>\n";
    echo implode(", ", $artistArray) . "<hr/>\n\n";
}
?>

    </body>
</html>

<?php

}


function renderAlbum($albumId)  {

global $accessToken;

$albumInfo = getAlbumInfo($albumId, $accessToken);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>TributeAlbum</title>
    </head>
    <body>
        <h1>TributeAlbum</h1>
        <h2><?= $albumInfo["name"] ?></h2>

<?php
echo "<img src='" . $albumInfo["images"]["1"]["url"] . "' width='300' height='300'/>\n";
echo "<ul>";
foreach ($albumInfo["tracks"]["items"] as $track) {

    $artistArray = array();
    foreach ($track["artists"] as $artist) {
        $artistArray[] =  $artist["name"];
    }

    echo "<li>" . $track["name"] ." (" . implode(", ", $artistArray) . ")</li>";
}
echo "</ul>";
?>
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



function getAlbumList($albumTitle, $accessToken) {

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


function getAlbumInfo($albumId, $accessToken) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/albums/' . $albumId);
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