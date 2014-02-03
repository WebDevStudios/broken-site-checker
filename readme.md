# Broken Site Checker #

Contributors: ryanduff  
Tags: multisite, admin, upgrade, management, network  
Requires at least: 3.8  
Tested up to: 3.8.1  
Stable tag: 1.0.1  
License: GPLv2 or later  

Checks all sites on a multisite and archives any that are inaccessable.

## Description ##

Large networks can get a bit unweidly. On occasion there are mapped domains that won't load and this breaks the network upgrade process. This loops through all "active" sites in a multisite network and archives any sites that can't be reached.

## Installation ##

1. Network Activate plugin and visit Network Admin > Sites > Broken Link Checker

## Frequently Asked Questions ##

None at this time

## Screenshots ##

1. Checking a multisite network for broken sites

## Changelog ##

### 1.0.1 ###
* Better validation of Site IDs before archiving
* Added wp_error response to archived site message
* Fixed missing string translation


### 1.0.0 ###
* Initial Release
