<?php 


include("config/config.php");


//http://altorouter.com/
require 'vendor/autoload.php';

$regexpPatterns = array(
    "/ - Remastered$/",
    "/ - [0-9]{4} Remaster$/",
    "/ - [0-9]{4} Digital Remastered.*$/",
    "/ - Live [0-9]{4}$/",
);

$accessToken = getToken($clientId, $clientSecret);
$albumTitle = "last summer dance";


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
echo "<ol>\n";


foreach ($albumInfo["tracks"]["items"] as $track) {

    $artistArray = array();
    foreach ($track["artists"] as $artist) {
        $artistArray[$artist["id"]] =  $artist["name"];
    }

    echo "  <li>" . $track["name"] . "\n";
    echo "    <ul>\n";
    $tributeArray = getTrackList($track["name"], $accessToken, $artistArray);
    //print_r($tributeArray);
    foreach ($tributeArray as $tributeTrack) {
        echo "      <li><a href='" . $tributeTrack["url"] . "'>" . $tributeTrack["name"];
        echo "</a> (".implode(", ", array_values($tributeTrack["artists"])).")</li>\n";
    }
    echo "    </ul>\n";
    echo "  </li>\n";

}
echo "</ol>\n";
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

    $apiEndpoint = "https://api.spotify.com/v1/search?q=".urlencode("album:$albumTitle")."&type=album&market=ES&limit=50";

    return curlCall($apiEndpoint, $accessToken);

}


function getAlbumInfo($albumId, $accessToken) {

    $apiEndpoint = "https://api.spotify.com/v1/albums/" . $albumId;
    
    return curlCall($apiEndpoint, $accessToken);

}


function getTrackList($trackTitle, $accessToken, $artistArray) {

    global $regexpPatterns;

    $trackTitle = preg_replace($regexpPatterns, "", $trackTitle);
    //echo "-- $trackTitle --";

    $apiEndpoint = "https://api.spotify.com/v1/search?q=".urlencode("track:$trackTitle")."&type=track&limit=50";

    $results = array();

    do {

        $json = curlCall($apiEndpoint, $accessToken);
        $counter++;
        $apiEndpoint = $json["tracks"]["next"];
        $results = array_merge($results, $json["tracks"]["items"]);
    
    } while (isset($json["tracks"]["next"]));

    //print_r($results);

    $trackList = array();

    foreach ($results as $key => $track) {

        $artistList = array();
        foreach ($track["artists"] as $artist) {
            $artistList[$artist["id"]] = $artist["name"];
        }

        if (implode("-", array_keys($artistArray)) == implode("-", array_keys($artistList))) {
            continue;
        }

        if (strtolower(substr($track["name"], 0, strlen($trackTitle)))!==strtolower($trackTitle)) {
            continue;
        }

        $trackList[$key]["artists"] = $artistList;
        $trackList[$key]["url"] = $track["external_urls"]["spotify"];
        $trackList[$key]["name"] = $track["name"];
    }

    //print_r($trackList);

    return $trackList;

}

function curlCall($apiEndpoint, $accessToken){

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
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
