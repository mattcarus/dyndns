dyndns
======

PHP Implementation of a DynDns server.

Based on http://warped.org/blog/2013/07/08/dyndns-for-bind/ - this has more comprehenside instructions :)

Installation
============

1. Copy dyndns.php to somewhere accessible (e.g. web root)

2. Create a key for each host to be updated:

    dnssec-keygen -a HMAC-MD5 -b 512 -n USER dynamichost.yourdomain.com.
    mkdir /var/named/dyndns
    cp K* /var/named/dyndns

3. Add Keys to each zone file

 key dynamichost.yourdomain.com. {
   algorithm HMAC-MD5;
   secret "secret from key file here==";
 };
 
 zone "yourdomain.com" {
        type master;
        file "/var/lib/bind/yourdomain.com.hosts";
        allow-transfer {
                127.0.0.1;
                localnets;
                buddyns;
                rollernet;
                };
        update-policy {
                grant dynamichost.yourdomain.com. name dynamichost.yourdomain.com. A;
        };
        };

4. Restart Bind

  service named restart

Perform Updates
===============

Using cURL:

(Note the backslash - needed to escape the ampersand)

curl --user username:password http://mydomain.com/dyndns.php?myip=auto\&hostname=myhostname.mydomain.com
