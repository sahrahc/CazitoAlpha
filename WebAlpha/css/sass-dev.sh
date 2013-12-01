#!/bin/bash
echo "Watching SASS"
sass --watch ./sass:./dev \
        --style expanded --no-cache --line-numbers

