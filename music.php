<?php
// Telegram bot token
$botToken = '7706550068:AAGHmrRrxuzE-m--jop12biUS3gNEKXCs6k';
$apiURL = "https://api.telegram.org/bot$botToken/";

// Spotify credentials
$spotifyClientId = 'd0eb4687f272412b958f73d6f1ac6365';
$spotifyClientSecret = '94094abf2a4e4c8783a72825f78639d0';

// Function to get Spotify access token
function getSpotifyAccessToken($clientId, $clientSecret) {
    $url = 'https://accounts.spotify.com/api/token';

    $headers = [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
    ];

    $postFields = [
        'grant_type' => 'client_credentials'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Function to search for a track on Spotify
function searchSpotifyTrack($accessToken, $query) {
    $url = 'https://api.spotify.com/v1/search?q=' . urlencode($query) . '&type=track&limit=1';
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['tracks']['items'][0])) {
        return $data['tracks']['items'][0]; // Return the first track found
    }

    return null;
}

// Function to send a message via Telegram
function sendMessage($chatId, $message) {
    global $apiURL;

    $url = $apiURL . "sendMessage?chat_id=$chatId&text=" . urlencode($message);
    file_get_contents($url);
}

// Get incoming updates from Telegram (if using webhook)
$update = json_decode(file_get_contents('php://input'), TRUE);

// Handle incoming message
if (isset($update['message'])) {
    $message = $update['message']['text'];
    $chatId = $update['message']['chat']['id'];

    // Check if user sent /start command
    if (strtolower($message) == '/start') {
        sendMessage($chatId, "Welcome to the Spotify Music Bot! Send the name of a song or artist to search.");
    } else {
        // Fetch access token from Spotify
        $spotifyAccessToken = getSpotifyAccessToken($spotifyClientId, $spotifyClientSecret);

        if ($spotifyAccessToken) {
            // Search Spotify for the track
            $track = searchSpotifyTrack($spotifyAccessToken, $message);

            if ($track) {
                // Get track details
                $trackName = $track['name'];
                $artistName = $track['artists'][0]['name'];
                $albumName = $track['album']['name'];
                $spotifyUrl = $track['external_urls']['spotify'];

                // Send track details to the user
                $responseMessage = "🎵 *Track:* $trackName\n👤 *Artist:* $artistName\n💿 *Album:* $albumName\n🔗 [Listen on Spotify]($spotifyUrl)";
                sendMessage($chatId, $responseMessage);
            } else {
                sendMessage($chatId, "Sorry, I couldn't find any tracks for '$message'.");
            }
        } else {
            sendMessage($chatId, "Error: Unable to authenticate with Spotify.");
        }
    }
}
?>