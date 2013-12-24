#!/bin/bash
echo "Minifying CSS"
sass --update \
		./sass:./min \
	 --style compressed --no-cache --force

