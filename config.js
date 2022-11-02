var mysql = require('mysql');
module.exports = {

    getDBConnection: function () {
        var db = mysql.createConnection({
            host: 'localhost',
            port: '3306',
            user: 'root',
            password: 'CooperApp234#',
            database: 'db_cooper_web'
        })
        db.connect(function(err){
            if (err) {
                console.log('DB connection : ' + err);
                process.exit(1);
            }
        })
        return db;
    }
}
