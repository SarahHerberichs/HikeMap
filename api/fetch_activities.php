<?php
header('Content-Type: application/json');

$client_id = '180269';
$client_secret = '4146ee890c98c4d563ae3fb62ad717419e39d127';
$tokenFile = __DIR__ . '/../api/tokens.json';

if (!file_exists($tokenFile)) {
  echo json_encode(["error" => "No token found"]);
  exit;
}

$tokens = json_decode(file_get_contents($tokenFile), true);
if (!$tokens) {
  echo json_encode(["error" => "Invalid token file"]);
  exit;
}

// Rafraîchir si expiré
$now = time();
if ($tokens['expires_at'] <= $now) {
  $refreshUrl = "https://www.strava.com/oauth/token";
  $postFields = http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'refresh_token',
    'refresh_token' => $tokens['refresh_token']
  ]);
  $ch = curl_init($refreshUrl);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields
  ]);
  $resp = curl_exec($ch);
  curl_close($ch);
  //Récup la data des tokens et mets dans le fichier
  $data = json_decode($resp, true);
  if (isset($data['access_token'])) {
    $tokens = array_merge($tokens, [
      'access_token' => $data['access_token'],
      'refresh_token' => $data['refresh_token'],
      'expires_at' => $data['expires_at']
    ]);
    file_put_contents($tokenFile, json_encode($tokens, JSON_PRETTY_PRINT));
  } else {
    echo json_encode(["error" => "Failed to refresh token", "details" => $data]);
    exit;
  }
}

$accessToken = $tokens['access_token'];
$activityId = $_GET['id'] ?? null;

if ($activityId) {
  // détail d'une activité
  $url = "https://www.strava.com/api/v3/activities/" . intval($activityId);
} else {
  // liste des activités
  $url = "https://www.strava.com/api/v3/athlete/activities?per_page=20&page=1";
}

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
]);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
