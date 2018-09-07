#!/usr/bin/env bash

# If the amount of arguments passed onto this script is greater than 0
if [ $# -ne 1 ]; then

    echo "Provide a version to test" >&2
    exit 1

fi

filename=$1
file_path=./tests/scripts/"$1".sh

if [ ! -f ${file_path} ]; then

  echo "File $file_path not found!" >&2
  exit 1

fi

sh ${file_path}