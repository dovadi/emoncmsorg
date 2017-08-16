# Emoncms.org variant of emoncms

This variant of emoncms is the version of emoncms running on emoncms.org, it focuses on delivering a scalable multi server implementation.

Unlike the main version of emoncms this version does not support all environments and options. It is designed for Linux servers (typically ubuntu) and focuses on supporting a sub set of feed engines, input processors and visualisations - reflecting the current emoncms.org build. Redis is required. Communication between servers is currently authenticated and encrypted with stunnel and emoncms.org supports https using letsencrypt.

The main intention with this repository is to make the current state of emoncms.org source code open source. It is a custom build of emoncms for emoncms.org. Developments developed on this branch such as turning feed engines into services for multi-server operation might be good to include in the main emoncms repository.

For the full build of emoncms see the main repository here https://github.com/emoncms/emoncms.git

### Key Features

- PHPFina feeds can be run as a service on a 2nd storage server, requests are forwarded from main server and secured with stunnel.
- Data to be written to storage server is transferred via a queue + socket stream for efficient transport and secured with stunnel.
- Faster input post/bulk pipeline in order to release apache connection as fast as possible
- Input processing queue's to reduce apache connection time as above.
- Simplified feed model code, redis always required.

### Installation and setup

1) Copy emoncms folder to /var/www/emoncms

2) Create mysql database

3) Copy default.settings.php to settings.php

4) Enter mysql database settings

5) The default data locations are /var/lib/phpfina and /var/lib/phptimeseries if you wish to change these set accordingly.

6) Open in browser, register new user (the first user created will be an admin user)

7) Send a test input: i.e:

    http://localhost/emoncms/input/post.json?node=1&csv=100,200,300
    
At this point no input will be created as we need to start up an inputqueue processor.

### Setting up an input queue processor

1) Create script-settings.php from default.script-settings.php in MainServerScripts

2) Copy script-settings.php to /etc/emoncms/script-settings.php

    sudo cp script-settings.php /etc/emoncms/script-settings.php

3) Set default script throttle delay's (these need to be adjusted to account for load and input/feed throughput)

    $ php MainServerScripts/set-usleep.php

3) Run input_queue_processor_1.php from terminal temporarily for testing:

    $ sudo php MainServerScripts/inputqueue/input_queue_processor_1.php

The output should look like this:

    Start of error log file
    Buffer length: 0 3000 1
    Buffer length: 0 3000 0
    Buffer length: 0 3000 0
    
3 inputs should now appear in the input list. 

### Storage Server setup

Create a feed from the inputs created above. While the feed appears to update no data will be written to disk as the datapoints are being queued up in a storage server queue.

Run storageserver0.php from terminal temporarily for testing:

    $ sudo php MainServerScripts/storageserver/storageserver0.php
    
Date should now be written to disk.

To view data the graph module needs to be installed in emoncms/Modules/graph, this can be done using git:

    git clone https://github.com/emoncms/graph.git
    
Update the mysql database from the administration interface once installed to create the graph module table.

### Advanced

- [Stunnel configuration](stunnel.md)


### Architecture

![Architecture1](docs/images/emoncms_scale.png)

### Licence

All Emoncms code is released under the GNU Affero General Public License. See COPYRIGHT.txt and LICENSE.txt.
