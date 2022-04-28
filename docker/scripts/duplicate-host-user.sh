#!/bin/sh

echo "Checking for existence of user with ID: ${USER_ID}"

id -u $USER_ID > /dev/null 2>&1

if [ $? -ne 0 ] && [ ${USER_ID:-0} -ne 0 ]; then
    echo "Creating user with ID: ${USER_ID}"
    adduser -u ${USER_ID} -D -s /bin/bash user user
fi
