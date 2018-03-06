<?php

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
	$public_key = '/var/www/auth/id_rsa.pub';
	$private_key = '/var/www/auth/id_rsa';
	$passphrase = 'penicillin';
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
	echo $newLoc . "\n";
	echo "/storage/disqs/" . $user . "/pdb_tmp/" . $name . "\n"; 
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
	echo $qsub_cmd;
	if (!(ssh2_exec($conn_ssh, $qsub_cmd))) {
		endOp($ssh_error . 3);
	}
	
	//echo print_r(posix_getpwuid(posix_geteuid()));
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
