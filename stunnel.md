# Securing link between main server and storage server with stunnel

I found the following guide useful - in particular the comment thread on the first and instructions by ericb on setting up with verify = 3.

- [How To Set Up an SSL Tunnel Using Stunnel on Ubuntu](https://www.digitalocean.com/community/tutorials/how-to-set-up-an-ssl-tunnel-using-stunnel-on-ubuntu)

- [Sending redis traffic through an SSL tunnel with stunnel](http://bencane.com/2014/02/18/sending-redis-traffic-through-an-ssl-tunnel-with-stunnel)

In addition to stunnel, access is limited using firewall rules to the storage or main server ip address only.

### Main Server conf

    pid = /var/run/stunnel4/stunnel.pid

    [emoncms]
        client = yes
        accept = 8080
        connect = STORAGE-IP:8080
        cert = /etc/stunnel/mainserver.crt
        key = /etc/stunnel/mainserver.key
        CAfile = /etc/stunnel/storageserver.crt
        verify = 3

    [emonsocket]
        client = no
        accept = SOCKETPORT-EXT
        connect = 127.0.0.1:SOCKETPORT-INT
        cert = /etc/stunnel/mainserver.crt
        key = /etc/stunnel/mainserver.key
        CAfile = /etc/stunnel/storageserver.crt
        verify = 3


### Storage Server conf

    pid = /var/run/stunnel4/stunnel.pid

    [emoncms]
        client = no
        accept = 8080
        connect = 127.0.0.1:80
        cert = /etc/stunnel/storageserver.crt
        key = /etc/stunnel/storageserver.key
        CAfile = /etc/stunnel/mainserver.crt
        verify = 3

    [emonsocket]
        client = yes
        accept = SOCKETPORT
        connect = MAIN-IP:SOCKETPORT
        cert = /etc/stunnel/storageserver.crt
        key = /etc/stunnel/storageserver.key
        CAfile = /etc/stunnel/mainserver.crt
        verify = 3
