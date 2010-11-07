#!/bin/bash

aspell --lang=en create master ./custom.rws < words.txt
sudo cp custom.rws /usr/lib/aspell/

