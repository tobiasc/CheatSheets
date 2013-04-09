var mongoose = require('mongoose');
mongoose.connect('mongodb://localhost/test');

var db = mongoose.connection;
db.on('error', console.error.bind(console, 'connection error:'));
db.once('open', function callback () {
	var kittySchema = mongoose.Schema({
	    name: String
	});
	kittySchema.methods.speak = function () {
	  var greeting = this.name
	    ? "Meow name is " + this.name
	    : "I don't have a name"
	  console.log(greeting);
	}
	var Kitten = mongoose.model('Kitten', kittySchema);

/*
	var fluffy = new Kitten({ name: 'fluffy' });
	fluffy.speak(); // "Meow name is fluffy"

	fluffy.save(function (err, fluffy) {
	  if (err) {
		console.log(err);
	  }
	  fluffy.speak();
	});
*/

	Kitten.find({ name: 'fluffy' }, function (err, kittens) {
	  if (err) {
		console.log(err);
	  }
	  console.log(kittens);
	});

/*	Kitten.find(function (err, kittens) {
	  if (err) // TODO handle err
	  console.log(kittens)
	});*/
	//Kitten.find({ name: /^Fluff/ });
	//Kitten.find();
});


