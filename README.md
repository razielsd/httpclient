HttpClient
==========

Simple http client for testing, save session between request

Example
---------
---------------------------------------------------
   $client = new Testing_Core_HttpClient();
    $client->setParam(
        Testing_Core_HttpClient::USER_AGENT, 'My Custom User Agent'
    );
    $client->setHost('example.com');//set default host
    $html = $client->openUrl('/profile');//open url using default host
    //some check for html
    $client->post('login', $user['login']);//init post param
    $client->post('password', $user['passwd']);
    $client->post('submit', 'logon');
    $client->setUrl('/profile/login');//set url
    $html = $client->send();//send post request
---------------------------------------------------