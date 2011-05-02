/*
 * Socket IO server / Redis client.
 * Allows connections from HTML page ans sets up a subscriber client on a Redis instance.
 * 
 */

// Include require modules.
var io = require('socket.io'); 
var redis = require('redis');
var http = require('http');  
var sys = require('sys');
var fs = require('fs'),
    index;
 
fs.readFile('./socket.html', function (err, data) {
    if (err) {
        throw err;
    }
    index = data;
});

// Redis config settings.
var redisHost = '';
var redisPort = 6379;
var redisPass = '';

// Create a web server for Socket IO to use.
var server = http.createServer(function(req, res){
 res.writeHead(200, {'Content-Type': 'text/html'}); 
 // res.end('<h1>This is a node web server.</h1>'); 
  res.write(index);
  res.end();
});

// Specify the port the server should listen on.
server.listen(8000);

// Create a new socket
var socket = io.listen(server); 

// Listen for connection from clients.
socket.on('connection', function(client){
	
	// A client has connected.
	sys.puts('Got a new connection!');
	
	// Create a Redis client and subscribe.
	var redisClient = redis.createClient(redisPort, redisHost); 
	redisClient.auth(redisPass);
	redisClient.subscribe("tropo.color");
	
	// Handler for Redis client subscription.
	redisClient.on("subscribe", function (channel) {
		sys.puts("Redis Client Subscribed to " + channel);
	});
	
	// When a message comes in on the subscribed channel, send it to the connected web client.
	redisClient.on("message", function (channel, message) {
      sys.puts("redisClient channel " + channel + ":  message " + message);
      client.send(message);
	});
 
}); 
