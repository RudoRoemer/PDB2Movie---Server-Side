#!/bin/bash
remoteUser=phsbqz
remoteServer=godzilla.csc.warwick.ac.uk

ssh $remoteUser@$remoteServer 'cd /storage/disqs/phsbqz && ./garbageCollection.sh'
