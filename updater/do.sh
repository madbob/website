#!/bin/bash

export IFS=$'
'

php engine.php
newdata=`php render.php`

start=`grep "<\!-- DYNAMIC UPDATE START -->" -n ../index.html | cut -d':' -f 1`
end=`grep "<\!-- DYNAMIC UPDATE END -->" -n ../index.html | cut -d':' -f 1`
head -n $start ../index.html > /tmp/website_index.html
echo $newdata  >> /tmp/website_index.html
tail -n +$end ../index.html >> /tmp/website_index.html

mv /tmp/website_index.html ../index.html

