# Changelog

## v1.2.4 2015-04-15

  * Only use format=flowed with text/plain and not with other text/* stuff

## v1.2.3 2015-04-15

  * Maintenace release, bumped dependency versions

## v1.2.2 2015-04-03

  * Maintenace release, bumped libqp which resolves an endless loop in case of a trailing &lt;CR&gt;

## v1.2.1 2014-09-12

  * Maintenace release, fixed a test and bumped dependency versions

## v1.2.0 2014-09-12

  * Allow functions as transform plugins (the function should create a stream object)

## v1.1.1 2014-08-21

  * Bumped libmime version to handle filenames with spaces properly. Short ascii only names with spaces were left unquoted.

## v1.1.0 2014-07-24

  * Added new method `getAddresses` that returns all used addresses as a structured object
  * Changed version number scheme. Major is now 1 but it is not backwards incopatible with 0.x, as only the scheme changed but not the content