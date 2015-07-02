#!/bin/bash

comment=$1
filename=$2
key_command='ssh-keygen -t rsa -b 4096'

if [ ! -z $comment ]; then
	key_command="$key_command -C $comment"
fi

if [ ! -z $filename ]; then
	key_command="$key_command -f $filename"
fi

echo "Running: $key_command"

$key_command

exit 0


	
