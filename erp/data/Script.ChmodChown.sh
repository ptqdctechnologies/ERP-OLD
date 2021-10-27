#!/bin/bash

clear;
i=0;
#for file in `ls /srv/www/erp/data/files/ | xargs -L 100` ; do
for file in `find /srv/www/erp/data/files/ -type f | xargs -L 100` ; do
   (( i++ ));
   echo $i;
#echo $file;
   chown www-data:www-data $file;

#find /srv/www/erp/data/files/ -type f | xargs chown www-data:www-data '{}'


#   echo '--------------------';
   #echo $file;
   #cd /srv/www/erp/data/files/;
   #chown www-data:www-data $file;
   #cd -;
   #chown www-data:www-data /srv/www/erp/data/files/$file;
done

#find /srv/www/erp/data/files/ -type f | xargs -i chown www-data:www-data '{}';
