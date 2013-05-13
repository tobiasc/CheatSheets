Result = new mongoose.Schema({
    name : String
});
mongoose.model('Result', Result);

var Results = mongoose.model('Result', Result);
exports.Results = Results;

