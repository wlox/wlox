#!/bin/sh

DIR=$(cd "$( dirname "$0" )" && pwd)
while true; do
    if ! type "$node" > /dev/null; then
		node $DIR/lib/js/sockets.js
	elif ! type "$nodejs" > /dev/null; then
		nodejs $DIR/lib/js/sockets.js
	fi
done
