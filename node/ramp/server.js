var express = require('express'),
    mongoose = require('mongoose'),
    https = require('https'),
    http = require('http'),
    fs = require('fs'),
    result = require('./models/result'),
    queryResultLine = require('./views/queryResultLineView'),
    security = require('./utilities/security');

// Set up models
var Schema = mongoose.Schema;
var ObjectId = Schema.ObjectId;
mongoose.connect('mongodb://localhost:27017/ramp');

var result = new result.Result(); 

var app = express();
 
app.configure(function () {
    app.use(express.logger('dev')); // 'default', 'short', 'tiny', 'dev' 
    app.use(express.bodyParser());
});

// HTTPS Settings
var options = {
    key: fs.readFileSync('key.pem'),
    cert: fs.readFileSync('cert.pem')
};

// Authenticate all identities, but only on call not containing a ".", i.e. all .html pages, images, etc.
app.all(/^[a-z]$/i, security.identityAuthentification);

// Routes
app.get('/QueryResultLine', queryResultLine.find);

// Specify listening ports and services
http.createServer(app).listen(2000);
https.createServer(options, app).listen(3000);
console.log('Listening on port 2000...');

