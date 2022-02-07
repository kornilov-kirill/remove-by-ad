<?php

//LDAP CONFIG
$use_ntlm_auth = true;
$ntlm_user = ''; // domain/user
$ntlm_password = '';
$project_url = 'https://office-map.ru/Staffmap%2044new2/sibur0511';
$apikey = '';

$ldap_url = "ldap://185.220.34.144/:389";
$ldap_user = '';
$ldap_pass = '';
$ldap_max_counter = 20000;


$dn = "DC=office-map,DC=ru";
$search_filter = "(&(objectCategory=person)(samaccountname=*))";


//STAFFMAP CONFIG

// SQL: SELECT [username] FROM _EMPLOYEE (создать лист типа employee и поставить галочку только на username)
$all_employees_url = '';
