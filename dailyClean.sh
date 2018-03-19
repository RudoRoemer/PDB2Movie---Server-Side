#!/bin/bash
remoteUser=phsbqz
remoteServer=godzilla.csc.warwick.ac.uk
log="/var/www/html/TEST.txt"

echo "This ran $(date), files delete:">>$log

for file in ./download/* ; do
        if [[ $(find "$file" -mtime +7 -print) ]]; then
                echo '    '$file>>$log
                rm $file
        fi
done

echo "This ran $(date)">>$log

ssh $remoteUser@$remoteServer 'cd /storage/disqs/phsbqz && ./garbageCollection.sh'
