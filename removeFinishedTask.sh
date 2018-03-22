#!/bin/bash

purename=$1
log=/var/www/html/query.txt

mysql -u admin -p'penicillin_loves_beta_lactamase' -D db_pdb2movie -e"DELETE FROM Requests WHERE filename='$purename'"

echo $query>>$log
eval $query
