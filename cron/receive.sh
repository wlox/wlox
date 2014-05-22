#!/bin/bash

DIR=$(pwd -P)
F=$DIR/transactions/${1}

echo ${1} >> ${F}
