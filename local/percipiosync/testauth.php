<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.percipio.com/reporting/v1/organizations/181e2a21-703a-4ce9-b998-88249f285c91/report-requests/learning-activity',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
"start":"2023-02-01T13:00:40Z",
"end":"2023-03-01T13:00:40Z",
"audience":"ALL",
"contentType":"Course",
"isFileRequiredInSftp":false,
"formatType":"JSON",
"status":"COMPLETED",
"includeMillisInFilename":false
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzZXJ2aWNlX2FjY291bnRfaWQiOiIyYTgzNjVmMS05YzZhLTRiZjEtOGE0Ny01MDQxMzVmZmYzYWUiLCJvcmdhbml6YXRpb25faWQiOiIxODFlMmEyMS03MDNhLTRjZTktYjk5OC04ODI0OWYyODVjOTEiLCJpc3MiOiJhcGkucGVyY2lwaW8uY29tIiwiaWF0IjoxNjM3NTQzNDgyLCJzdWIiOiIyNmEwZTkyYzk0YWJhMTBjYTQ5OGUxZDU2Mjk0NzY4NTQzNzQ0MzdlIiwicG9saWN5LWlkIjoicGVyY2lwaW8tYXBpLXN0YW5kYXJkLXBvbGljeSJ9.B44T-oniRpXCwO7ZoXrWiXNiJ9s51i4fW4WOrwbtaKQj1IGdZZy34JJoVjoqi64Z0TJi_zimE3uUepf0I4IMcvKgCW0_iJocUF6LYQo3ZZWwoaWt51k1zLTYDz1_yrklEvTyBkLo8W4vGQuMm-K1EmP6QFETZIziHVLfx0stZEb9ta5uPDALuRwDqU9vP8DFPrxcaBY-G-N43QdKO3I8K2I0cMw4AOySAuvK8Cvoxd7oy9RVPH8vladKYeHkjsZFjDrXvZbptTO_vZMszBPcRqEaAVT6qoKJuwPkpRWUkYnAkx3mHTKiQ7gIG2cAIgkEgnaqYDA4skNDqNOs0FfiOcCsnvetvGpo21yAKtwYkGfPLyOdiKWu8ldH0rnWkibg7T43DbajTeKdK1cx2v5BhV7BwRvWbFaMlf7dJsCPmdwgIhqx03hNxSuldxnC-7tjQYanj687810CvTSMvHMI6YRaRpFxnUTY8EwHOh6tieZTGWAMXtVxgDjJGk2YC4MLDHLrbM207wK8vvFxycyMxd7YRluJSct50cUHJkC0w0HbF9yfHo2LpcNPKwnsQrQYPBKxYxAV6IJXxZ15QpfZolZ1l8-ObREcM4LBfWSgHxjHmc3oTC_uuQC8lh2gwPKqzjNLxQgNfTPbjD5U-zz9L6eZiXsO_YendtRIN0XXAFM'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
