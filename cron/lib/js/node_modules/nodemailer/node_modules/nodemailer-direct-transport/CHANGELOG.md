# Changelog

## v1.0.2 2015-03-09

Bumped smtp-connection version and replaced simplesmtp based tests with smtp-server based ones.

## v1.0.0 2014-07-30

Fixed a bug with stream buffering. Uses [mail.resolveContent](https://github.com/andris9/Nodemailer#resolvecontent) provided by Nodemailer v1.1.

As the change includes a method from Nodemailer 1.1 and not 1.0, then changed the version scheme to use proper semver instead of 0.x.