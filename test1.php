<div class="singlebutton m-b-1">
    <form method="get" action="http://localhost/moodle4/mod/customcert/view.php">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="downloadownn" value="1">
        <button type="submit" class="btn btn-primary" id="single_button65cc52ef65e1a2">Download</button>
    </form>
</div>
<?php

// Initialize cURL session
$ch = curl_init();

// Set the URL
curl_setopt($ch, CURLOPT_URL, "https://jsonplaceholder.typicode.com/users/2");

// Return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if(curl_errno($ch)){
    echo 'Curl error: ' . curl_error($ch);
}

// Close the session
curl_close($ch);

// Display the response
echo '<pre>';
echo $response;
echo '<pre>';
