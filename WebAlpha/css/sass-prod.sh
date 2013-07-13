#!/bin/bash
echo "Minifying CSS"
sass --update \
		../www/themes/Todos/css/sass:../www/themes/Todos/css/min \
		../www/themes/Vida/css/sass:../www/themes/Vida/css/min \
	 --style compressed --no-cache --force

