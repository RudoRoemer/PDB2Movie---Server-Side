<?php

	$configs = parse_ini_file("/var/www/html/config.conf");

	die(var_dump(posix_getpwuid(posix_getpgid())));

	include "FileChecker.php";
	include "PdbChecker.php";
	include "PythonChecker.php";

	//make sure the user has agreed to terms of service on the server side.
	if (!$_POST["tos"] === true) {
		die("The Terms of Service were not accepted.");
	}

	$sha1Args = sha1($_POST["confs"] . $_POST["freq"] . $_POST["step"] . $_POST["dstep"] . $_POST["email"] . $_POST["modList"] . $_POST["molList"] . $_POST["cutList"]);
	$sha1File = sha1_file($_FILES['pdbFile']['tmp_name']);
	$sha1Final = sha1($sha1Args . $sha1File);

 	$pdbFile = new PdbChecker($sha1Final);
	if ($pdbFile->didItPass() !== "Success"){
    		endOp(".pdb file: " . $pdbFile->didItPass());
	}
	$newLoc = $pdbFile->getTmpLocation();	
	$pyFileUsed = file_exists($_FILES['pyFile']['tmp_name']);
	if ($pyFileUsed) {
        	$pythonFile = new PythonChecker(sha1Final);
        	if ($pyFile->didItPass() !== "Success"){
            		endOp(".py file: " . $pyFile->didItPass());
		} else {
			move_uploaded_file($_FILES['pyFile']['tmp_name'], $pyFile->getTmpLocation());
		}
	}
	
	move_uploaded_file($_FILES['pdbFile']['tmp_name'], $pdbFile->getTmpLocation());
	
	//validates inputs, ends program if erroneous 
                //move file to tmp location.
	switch (false) {
		case filter_var($_POST["confs"]				, FILTER_VALIDATE_INT);
			endOp("Invalid value in configuration parameter.");
		case filter_var($_POST["freq"]				, FILTER_VALIDATE_INT);	
			endOp("Invalid value in frequency parameter.");
		case filter_var($_POST["step"]				, FILTER_VALIDATE_FLOAT);
			endOp("Invalid value in random step parameter.");
		case filter_var($_POST["dstep"]				, FILTER_VALIDATE_FLOAT);
			endOp("Invalid value in direct step parameter.");
		case filter_var($_POST["email"]				, FILTER_VALIDATE_EMAIL);
			endOp("Invalid email");
	}
	$invBool = "Invalid boolean.";
	if (filter_var($_POST["combi"], FILTER_VALIDATE_BOOLEAN) === NULL) {
		endOp("invalid BOOL");
	}
	if (filter_var($_POST["waters"], FILTER_VALIDATE_BOOLEAN) === NULL) {
                endOp("invalid BOOL");
        }
	if (filter_var($_POST["threed"], FILTER_VALIDATE_BOOLEAN) === NULL) {
                endOp("invalid BOOL");
        }
	if (filter_var($_POST["multiple"], FILTER_VALIDATE_BOOLEAN) === NULL) {
                endOp("invalid BOOL");
        }

	$file=file_get_contents( $newLoc );

	//script would have ended if any value is not sanitized, good to send.
	$name = ltrim($newLoc, "/var/www/html/php/pdb_tmp/");
	$pyName = ltrim($newLocPyth, "/var/www/html/php/pdb_tmp/");
	$res = $_POST["res"];
	$waters = $_POST["waters"];
	$combi = $_POST["combi"];
	$multiple = $_POST["multiple"];
	$threed = $_POST["threed"];
	$confs = $_POST["confs"];
	$freq = $_POST["freq"];
	$step = $_POST["step"];
	$dstep = $_POST["dstep"];
	$email = $_POST["email"];
	$molList = $_POST["molList"];
	$modList = $_POST["modList"];
	$cutList = $_POST["cutList"];

	//server stores email and file type for when process is finished to email back.
	$server = "localhost";
	$db_user = "root";
	$pass = "";
	$db = "pdb2movie";

	$conn_ssh;
	$remote_host = 'godzilla.csc.warwick.ac.uk';
	$remote_host_fp = "CC170F7C369A5603B18ED7729CE243AD";
	$user = 'phsbqz';
	$location='/storage/disqs/';
	$public_key = $configs["sshPublic"];
	$private_key = $configs["sshPrivate"];

	$ssh_error = "SSH command failed. Error on the processing server side.";
			
	//establish connection with remote SCRTP computer
	if(!($conn_ssh = ssh2_connect($remote_host, 22,  array('hostkey'=>'ssh-rsa')))) {
		endOp("Could not connect to server.");
	}
	
	$fingerprint = ssh2_fingerprint($conn_ssh, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
	
	if (strcmp($remote_host_fp, $fingerprint) !== 0) {
		echo $remote_host_fp . "\n";
		echo $fingerprint . "\n";
		endOp("cannot Identify Server.");
	}

	$auth = ssh2_auth_pubkey_file($conn_ssh, $user, $public_key, $private_key);	

	if (!$auth) {
		var_dump($auth);
		endOp("authentication failure. Check with admin.\n");
	}
	
	
	$output;
	$rawname = rtrim($name, '.pdb');
	//echo $newLoc . "\n";
	//echo "/storage/disqs/" . $user . "/pdb_tmp/" . $name . "\n";
	if (!(ssh2_scp_send($conn_ssh, $newLoc, "/storage/disqs/" . $user . "/pdb_tmp/" . $name ))) {
		endOp("Error uploading pdb file to processing server.");
	}
	if ($pyFileUsed) {
		if (!(ssh2_scp_send($conn_ssh, $newLocPyth, "/storage/disqs/" . $user . "/pdb_tmp/" . $pyName ))) {
			endOp("Error uploading python file to processing server.");
		}
	}
	//if the optional params are empty or equal spaces then add "NULL" so argument fits syntax of qsub on torque server whilst and for code to remove from qsub submission
	if ($molList == "") {
		$molList = "NULL";
	}
        if ($modList == "") {
                $modList = "NULL";
        }
        if ($cutList == "") {
                $cutList = "NULL";
        }

	$qsub_cmd = sprintf('cd %s%s && qsub -N %s -v LOC="%s",USER="%s",NAME="%s",RES="%s",WATERS="%s",COMBI="%s",MULTIPLE="%s",THREED="%s",CONFS="%s",FREQ="%s",STEP="%s",DSTEP="%s",EMAIL="%s",MOLLIST="%s",MODLIST="%s",CUTLIST="%s" -q taskfarm %s%s/submit.pbs', 	
	$location,
	$user,
	$name,
	$location,
	$user, 
	$name, 
	$res, 
	$waters, 
	$combi, 
	$multiple, 
	$threed, 
	$confs, 
	$freq, 
	$step, 
	$dstep, 
	$email, 
	$molList, 
	$modList, 
	$cutList,
	$location,
	$user);

	//endOp("Request Sent. DB not connected stmt->getResult() undefined. requires more up-to-date PHP, will be sorted.");

	$sqlServer = $configs["sqlServer"];
	$sqlUser = $configs["sqlUser"];
	$sqlPass = $configs["sqlPassword"];
	$sqlDB = $configs["sqlDB"];

	//if connection fails, stop script
	$conn_sql = mysqli_connect($sqlServer,$sqlUser, $sqlPass, $sqlDB) or die("Connection failed: " . mysql_connect_error());

	$getID = "SELECT user_id FROM Users WHERE email=?;";

	$stmt = $conn_sql->stmt_init();
    $stmt = $conn_sql->prepare($getID);
    $stmt->bind_param("s", $email);
    $stmt->execute();
	$sqlRes = $stmt->get_result();
	$row = $sqlRes->fetch_assoc();

	if (!$row) {
        $stmt = $conn_sql->stmt_init();
        $stmt = $conn_sql->prepare("INSERT INTO Users VALUES (?, 3, NULL);");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $emails_id = mysqli_stmt_insert_id($stmt);

	} else {

		$emails_id = $row["user_id"];

	}

	//while ($row = $sqlRes->fetch_assoc()) {
	//	print($row["email"]);
	//}

	$stmt1 = $conn_sql->stmt_init();
	//"SELECT Requests.req_id FROM Users INNER JOIN Requests ON Users.user_id = Requests.user_id WHERE Users.email=? AND Requests.filename=? AND Requests.resolution=? AND Requests.combi=? AND Requests.multi=? AND Requests.waters=? AND Requests.threed=? AND Requests.confs=? AND Requests.freq=? AND Requests.step=? AND Requests.dstep=? AND Requests.molList=? AND Requests.modList=? AND Requests.cutList=?;"
	//bind_param("sssiiiiiiddsss", $email, $rawname, $res, intval($combi), intval($multiple), intval($waters), intval($threed), $confs, $freq, $step, $dstep, $molList, $modList, $cutList);
	$stmt1 = $conn_sql->prepare("SELECT Requests.req_id, Users.max_requests FROM Requests INNER JOIN Users ON Requests.user_id = Users.user_id WHERE Users.email=?");
	$stmt1->bind_param("s", $email);
	$stmt1->execute();

	$stmtRes = $stmt1->get_result();
	$currReqs = $stmtRes->num_rows;
	$row = $stmtRes->fetch_assoc();

	if ($row["max_requests"] >= $currReqs) {

		$stmt2 = $conn_sql->stmt_init();
        $stmt2 = $conn_sql->prepare("INSERT INTO Requests (filename, python_used, resolution, combi, multi, waters, threed, confs, freq, step, dstep, molList, modList, cutList, req_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)");
        $stmt2->bind_param("sisiiiiiiddsssi", $rawname, $pyFileUsed, $res, $combi, $multiple, $waters, $threed, $confs, $freq, $step, $dstep, $molList, $modList, $cutList, $emails_id);

        if ($stmt2->execute()) {

        	if (!(ssh2_exec($conn_ssh, $qsub_cmd))) {
            		endOp("There was an error with your process. If you get this message, please email s.moffat.1@warwick.ac.uk");
            }

		} else {
        	endOp("There was an error adding your request to the queue: " . mysqli_stmt_error($stmt2));
		}
	} else {

		die("This request is already being processed. You will be emailed upon an update to your request.");

	}

	//echo print_r(posix_g	if () {
//etpwuid(posix_geteuid()));
	endOp("request submitted. Check your emails for updates and confirmation.\n DEV MODE: no email sent, check contents of /storage/disqs/phsbqz/pdb_des/ on CSC server.");

	//ends program and deletes file.
	function endOp($msg) {
		global $newLoc;
		global $conn_ssh;
		try {
			unlink( $newLoc );
		} catch ( RuntimeException $e ) {}
		try {
			ssh2_exec($conn_ssh, 'echo "EXITING" && exit;');
		} catch ( RuntimeException $e ) {}	
		die($msg);
	}

	//if connection fails, stop script
	//$conn_sql = mysqli_connect($server,$user, $pass, $db) or die("Connection failed: " . mysql_connect_error());
	
	//sql prerated statement
	// $stmt = $conn->prepare("INSERT INTO users VALUES (?, ?);");
	// $stmt->mysqli_bind_param("ss", $email, $name);
	// $stmt->execute();

?>
