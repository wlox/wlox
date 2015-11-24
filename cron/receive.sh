#!/bin/bash

DIR=$(cd "$( dirname "$0" )" && pwd)
F=$DIR/transactions/${1}

echo ${1} >> ${F}
