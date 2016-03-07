# Emoncms PHPFina & PHPFiwa Storage Server

## Security (stunnel and firewall rules)

Neither the socket client or the web server part here is secured natively with php or apache. Instead both are secured via a stunnel SSL/TLS connection to the main server with verify=3 (verify peer with locally installed certificate). A key and cert is generated on both the client and server and then the server's public cert is copied to the client and the clients cert is copied to the server.

In additon to this the firewall rules are set so that only connections from the IP address of the main server are accepted and likewise connections to the socket server on the main server are only accepted from the ip adress of the storage server.

## User transfer


