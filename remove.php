<?php

require_once('config.php');

if ($show_config) {
    echo PHP_EOL . "<br>" . " SETTINGS: ";
    //echo PHP_EOL."<br>"." ".$;
    echo PHP_EOL . "<br>" . "use_ntlm_auth " . $use_ntlm_auth;
    echo PHP_EOL . "<br>" . "ntlm_user " . $ntlm_user;
    echo PHP_EOL . "<br>" . "ntlm_password " . $ntlm_password;
    echo PHP_EOL . "<br>" . "project_url " . $project_url;
    echo PHP_EOL . "<br>" . "apikey " . $apikey;
    echo PHP_EOL . "<br>" . "ldap_url " . $ldap_url;
    echo PHP_EOL . "<br>" . "ldap_user " . $ldap_user;
    echo PHP_EOL . "<br>" . "ldap_pass " . $ldap_pass;
    echo PHP_EOL . "<br>" . "securitygroup_id " . $securitygroup_id;
    echo PHP_EOL . "<br>" . "dn " . $dn;
    echo PHP_EOL . "<br>" . "search_filter " . $search_filter;
}




function get_all_users() {
    global $use_ntlm_auth, $ntlm_user, $ntlm_password, $all_employees_url;

    $url = $all_employees_url;
    echo PHP_EOL . "<br>" . "get all users url " . $url;

    function_exists('curl_init') ? 0 : die('ERROR: LIBRARY CURL IS NOT CONNECTED');
    $ch = curl_init();
    $ch = $ch ? $ch : die('ERROR: Cannot init the curl connection');

    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";

    $options = [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $header,
    ];
    if ($use_ntlm_auth) {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_NTLM;
        $options[CURLOPT_USERPWD] = sprintf('%s:%s', $ntlm_user, $ntlm_password);
    }
    if (preg_match("/^https:/i", $url)) {
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($ch, $options);

    $json_response = curl_exec($ch);

    curl_close($ch);

    $response = json_decode($json_response, true);
    if (!$response) die('ERROR: Cannot get employees from staffmap');
    if (!isset($response['data'])) die('ERROR: Cannot get field data when getting employees from staffmap');
    $response = $response['data'];
    if (!is_array($response)) die('ERROR: data from staffmap is not array');
    if (!$response) die('ERROR: data array from staffmap is empty');

    return $response;
}


function get_id_by_username($username, $users) {
    $username = strtolower($username);
    foreach ($users as $user) {
        $staffmap_username = strtolower($user[1]);
        if ($staffmap_username == $username) {
            return $user[0];
        }
    }
    return false;
}

function removeUserFromStaffmap($id) {
    global $use_ntlm_auth, $ntlm_user, $ntlm_password, $project_url, $apikey;

    $url = "$project_url/api/Employee/$id?apikey=$apikey";
    // if ($show_success_added) {
    //     echo PHP_EOL . "<br>" . "add user url " . $url;
    // }

    function_exists('curl_init') ? 0 : die('ERROR: LIBRARY CURL IS NOT CONNECTED');
    $ch = curl_init();
    $ch = $ch ? $ch : die('ERROR: Cannot init the curl connection');

    $options = [
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
    ];
    if ($use_ntlm_auth) {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_NTLM;
        $options[CURLOPT_USERPWD] = sprintf('%s:%s', $ntlm_user, $ntlm_password);
        echo '<br>' . PHP_EOL . 'User: ' . $ntlm_user . ' Password: ' . $ntlm_user;
    }
    if (preg_match("/^https:/i", $url)) {
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }

    curl_setopt_array($ch, $options);

    $json_response = curl_exec($ch);
    $response = json_decode($json_response, true);
    echo '<br>' . PHP_EOL . 'Response: ' . $json_response;
    curl_close($ch);

    return $response;
}



function process_info($info) {
    global $all_users, $ldap__counter, $ldap_max_counter;
    for ($i = 0; $i < $info["count"]; $i++) {
        $record = $info[$i];

        $ldap__counter++;
        if ($ldap__counter > $ldap_max_counter) die('------------- more then 12000! ---------------');
        echo PHP_EOL . '<br> ' . '-----------------------------------';

        if (isset($record['samaccountname'][0])) {
            $username = $record['samaccountname'][0];
            echo PHP_EOL . '<br> ' . $ldap__counter . ': ' . $username;
        } else {
            $err = [];
            $err[] = 'no username';
            $err[] = $record;
            echo  PHP_EOL . '<br> ' . $ldap__counter . ': no username';
            continue;
        }

        $staffmap_user_id = get_id_by_username($username, $all_users);
        if ($staffmap_user_id) {
            removeUserFromStaffmap($staffmap_user_id);
        }
    }
}


//-------------------------------------------------------------------------------------------------------------
//-------------------------------------- START OF THE PROGRAM -------------------------------------------------
//-------------------------------------------------------------------------------------------------------------


echo PHP_EOL . "<br>" . PHP_EOL . "<br>" . " PROGRAM WAS STARTED" . " <br>" . PHP_EOL;

function_exists('ldap_connect') ? 0 : die('ERROR: LIBRARY LDAP IS NOT CONNECTED');
function_exists('oci_connect') ? 0 : die('ERROR: LIBRARY OCI IS NOT CONNECTED');

$ds = ldap_connect($ldap_url);
$ds = $ds ? $ds : die('ERROR: Cannot connect to LDAP');

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
ldap_set_option($ds, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search


$all_users = get_all_users();
$ldap__counter = 0;

$errors_record = [];

echo PHP_EOL . "<br>" . ' USERNAMES:';

$x = '';
$xx1 = null;
$xx2 = null;
$xx3 = null;
$xx4 = [];
$controls = [];
$counter = 0;
while (true) {
    $bind_res = ldap_bind($ds, $ldap_user, $ldap_pass);
    $bind_res = $bind_res ? $bind_res : die('ERROR: Cannot bind to LDAP');

    $sr = ldap_search($ds, $dn, $search_filter, ['*'], 0, 0, 0, LDAP_DEREF_NEVER, [
        ['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => 50, 'cookie' => $x]]
    ]);
    ldap_parse_result($ds, $sr, $xx1, $xx2, $xx3, $xx4, $controls);
    $sr ? 0 : die(ldap_error($ds));
    $info = ldap_get_entries($ds, $sr);
    $info ? 0 : die(ldap_error($ds));

    echo PHP_EOL . "<br>" . "page {$counter}";

    process_info($info);
    if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
        $x = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
    } else {
        $x = '';
    }
    $counter++;
    if (!$x || !mb_strlen($x, 'UTF-8')) {
        break;
    }
}

echo PHP_EOL . '<br> ' . 'Received ' . $ldap__counter;

ldap_close($ds);
