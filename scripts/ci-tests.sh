#!/bin/sh

# Run unit tests inside of docker
cd /code/vendor/erdiko/users/tests/
phpunit AllTests
