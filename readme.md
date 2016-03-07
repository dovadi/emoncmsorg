# Emoncms.org variant of emoncms

This variant of emoncms is the version of emoncms running on emoncms.org, it focuses on delivering a scalable multi server implementation.

Unlike the main version of emoncms this version does not support all environments and options. It is designed for Linux servers (typically ubuntu) and focuses on supporting a sub set of feed engines, input processors and visualisations - reflecting the current emoncms.org build. Redis is required. Communication between servers is currently authenticated and encrypted with stunnel and emoncms.org supports https using letsencrypt.

The main intention with this repository is to make the current state of emoncms.org source code open source. It is a custom build of emoncms for emoncms.org. Developments developed on this branch such as turning feed engines into services for multi-server operation might be good to include in the main emoncms repository.

For the full build of emoncms see the main repository here https://github.com/emoncms/emoncms.git

### Key Features

- PHPFina and PHPFiwa can be run as services on a 2nd storage server, requests are forwarded from main server and secured with stunnel.
- Data to be written to storage server is transferred via a queue + socket stream for efficient transport and secured with stunnel.
- Faster input post/bulk pipeline in order to release apache connection as fast as possible
- Input processing queue's to reduce apache connection time as above.
- Simplified feed model code, redis always required.

### Documentation

- [Stunnel configuration](stunnel.md)
- [Setting up the input queue processors and socket server](queuesetup.md)

### Licence

All Emoncms code is released under the GNU Affero General Public License. See COPYRIGHT.txt and LICENSE.txt.
