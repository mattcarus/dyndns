<?php

// Path to Keyfiles
$dir = '/var/named/dyndns';

// Path to nsupdate binary
$nsupdate = '/usr/bin/nsupdate';

// Array of valid hosts and their usernames, zones and keyfile
$hosts = array(
  "myhost.mysubdomain.mydomain.com" => array(
    "user" => "myusername",
    "zone" => "mysubdomain.mydomain.com",
    "keyfile" => "Kmyhost.mysubdomain.mydomain.com.+157+03775.key"
  )
);

// No changes needed below here.

$query = array();

errorlog(explode(" ", $hosts));

foreach ( $_REQUEST as $key => $val )
{
  array_push($query, "$key:$val");
}

array_push($query, "_user:" . $_SERVER['PHP_AUTH_USER']);

error_log(explode(" ", $query));


$hostname = $_REQUEST['hostname'];
$myip = $_REQUEST['myip'];
$user = $_SERVER['PHP_AUTH_USER'];

# Check that we have the auth set and are sending non-blank stuff
if (!$hostname or !$myip or !$user)
{
    errorlog("not_blank");
    print "badauth\n";
    exit;
}

errorlog("Auth OK ($user)");

# Handle Auto-Discover of IP
if ($myip == "auto") {
        $myip = $_SERVER['REMOTE_ADDR'];
}

errorlog("MyIP: $myip");

# Check the IP address makes sense
if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $myip))
{
    errorlog("bad_ip");
    print "badauth\n";
    exit;
}

# Multiple hosts can be given, separated by a comma
$update_hosts = split(',',$hostname);
if (sizeof($update_hosts) > 10 ) {
    errorlog("too many");
    print "numhost\n";
    exit;
}

errorlog("Updating " . implode(" ", $update_hosts));

foreach ( $update_hosts as $update_host )
{
    # Check if it's a host we allow
    if (!isset($hosts[$update_host]))
        {
        errorlog("Bad host ($update_host not in " . print_r($hosts, TRUE) . ")");
        print "notfqdn\n";
        exit;
        }

    # Check that the user has access to this host
    if (strcmp($hosts[$update_host]['user'], $user)) {
        errorlog("Access Denied (user $user, expecting " . $hosts[$update_host]['user'] . ")");
        print "nohost\n";
        exit;
    }

        $key = sprintf("%s/%s", $dir, $hosts[$update_host]['keyfile']);
        $zone = $hosts[$update_host]['zone'];

        if ( !file_exists($key) )
        {
                errorlog("Key file $key missing or permission denied");
                exit;
        }

        # Perform the update
        file_put_contents("tmp.txt", "server $zone\nzone $zone\nupdate delete $update_host. A\nupdate add $update_host. 86400 A $myip\nshow\nsend\n");

        $exec = "$nsupdate -k $key -v tmp.txt";
        errorlog("Performing update ($exec)");
        errorlog(file_get_contents("tmp.txt"));

        exec($exec, $execOutput, $exitCode);

        foreach ( $execOutput as $execOutputLine )
        {
                if ( $execOutputLine != "" )
                {
                        errorlog("$execOutputLine");
                }
        }

        errorLog("Update exited with code: $exitCode");

        // Handle error
        //print "dnserr\n";
    //exit;

        errorlog("good $myip");
        print "good $myip\n";

}

function errorlog($msg)
{
  error_log("[" . date("c") . "] $msg\n", 3, "dyndns.log");
}
?>
