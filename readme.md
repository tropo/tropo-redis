Overview
========

A simple application demonstrating the power of combining Tropo, Redis, Node.js and jQuery.

Usage
=====

You'll need to following to run this example:

* A Tropo account (Create one for free or login at [Tropo.com](http://tropo.com/).
* An instance of Redis that external sources can connect to - [Redis to Go](https://redistogo.com/) is a good way to get this piece.
* A server running Node.js - this example was tested with v0.4.3.
* A webserver to serve the file socket.html (the HTML + jQuery page in this solution).

You will need to modify the files "tropo-redis.php" and "socket.js" to add the settings for your Reids instance.
Create a new Tropo Scripting application and use the "tropo-redis.php" file as the source file for the application.
Launch the file "socket.js" using Node.js (by default, this server will listen on localhost port 8000, change this if needed):

	node path/to/socket.js

If you are not running your socket.js server on localhost at port 8000, you will need to modify the file "socket.html" to point to your socket.io instance.
Once your socket.js server is running, load the socket.html page in your web browser.
You should now be able to call into your Tropo application (via the Skype number auto provisioned for your app, or by adding a phone number). 

Prerequisites
=============

The Node.js script in this example uses the [node-redis module](https://github.com/mranney/node_redis) by Matt Ranney and [socket.io](https://github.com/learnboost/socket.io-node) by LearnBoost 

Feedback
========

Feedback is appreciated. Send to mheadd [at] voxeo [dot] com.
