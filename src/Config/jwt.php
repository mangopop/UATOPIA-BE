<?php

return [
    'secret' => 'your_secret_key', // Change this to a secure key
    'algorithm' => 'HS256', // You can choose other algorithms as needed
    'issuer' => 'your_issuer', // Your application name or URL
    'audience' => 'your_audience', // The audience for the token
    'expiration' => 3600, // Token expiration time in seconds
];