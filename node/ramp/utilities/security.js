// This function first makes a check for whether the identity is given,
// if so it authentificates the identity.
exports.identityAuthentification = function(req, res, next){
    // Identity check
    if(req.query['user_id'] !== '' && req.query['user_id'] !== undefined && 
            req.query['user_hash'] !== '' && req.query['user_hash'] !== undefined){
        next();
    } else {
        next(new Error(401)); // 401 Not Authorized
    }
};
