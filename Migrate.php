<?php
/*
Plugin Name: Migrate
*/

session_start();

// creating a table user_data on activation of a plugin

register_activation_hook(__FILE__, 'create_plugin_database_table');
function create_plugin_database_table()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'user_data';
	$sql1 = "CREATE TABLE IF NOT EXISTS user_data 
			            (
                        uid INT  AUTO_INCREMENT PRIMARY KEY, 
                        host VARCHAR(30) NOT NULL,
                        username VARCHAR(30) NOT NULL,
                        password VARCHAR(50) NOT NULL,
                        dbname VARCHAR(50) NOT NULL,
						ftplogin VARCHAR(50) NOT NULL,
						ftppassword VARCHAR(50) NOT NULL
                        )";
	require_once (ABSPATH . 'wp-admin/includes/upgrade.php');

	dbDelta($sql1);
}

// creating log files containing insert, update and delete queries

add_filter('query',
function ($query)
{
	if (FALSE !== stripos($query, 'UPDATE ') || FALSE !== stripos($query, 'INSERT ') || FALSE !== stripos($query, 'DELETE ')) {
		$log_file_path = WP_CONTENT_DIR . '/sql.log';
		$log_file = fopen($log_file_path, 'a');
		if ($log_file && is_writeable($log_file_path)) file_put_contents($log_file_path, $query . "#@#" . PHP_EOL, FILE_APPEND | LOCK_EX);
	}

	return $query;
}, PHP_INT_MAX);


// creating an admin menu
// adding menus and submenus in dashboard

add_action('admin_menu', 'my_admin_menu');
function my_admin_menu()
{
	add_menu_page("Example Options", "MIGRATE", 4, "example-options", "migrate_admin_page", "dashicons-tickets");
	add_submenu_page("example-options", "Option 1", "PLUGIN SETTINGS", 4, "example-option-1", "plugin_details");
}

function plugin_details()
{

?>

	<!-- creating form which takes database details of any other system -->
	<form method="post">
		<br /><br />
		<h1>PRODUCTION DATABASE DETAILS</h1>
		<br /><br />
		NAME OF THE WEBSITE:<br />
		<input type="text" name="t1"><br />
		DB NAME:<br />
		<input type="text" name="t2"><br />
		DB USERNAME:<br />
		<input type="text" name="t3"><br />
		DB PASSWORD:<br />
		<input type="text" name="t4"><br />
		FTP LOGIN NAME:<br />
		<input type="text" name="t5"><br />
		FTP PASSWORD:<br />
		<input type="text" name="t6"><br />
		<center> <input type="submit" name="b1" id="plugin" value="SAVE DETAILS"> <center>
	</form>


<?php
}

// Called when save is clicked from settings page
if (isset($_POST['b1'])) {

	$username = $_POST['t3'];
	$password = $_POST['t4'];
	$database = $_POST['t2'];
	$host = $_POST['t1'];
	$ftplogin=$_POST['t5'];
	$ftppassword=$_POST['t6'];
    $ipaddress=gethostbyname($host);
	echo $ipaddress;
	session_start();
	$_SESSION["POST"] = $_POST;
	if (empty($username) || empty($password) || empty($database) || empty($host) || empty($ftplogin) || empty($ftppassword)) {
		echo "You did not fill the fields ";
	}
	else {
		require (ABSPATH . '/wp-config.php');

		$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$sql3 = "Select * from user_data";
		if ($result = mysqli_query($con, $sql3)) {
			$rowcount = mysqli_num_rows($result);
		}

		if ($rowcount < 1) {
			if ($stmt = mysqli_prepare($con, "INSERT INTO user_data(host,username,password,dbname,ftplogin,ftppassword) VALUES (?,?,?,?,?,?)")) {
				mysqli_stmt_bind_param($stmt, "ssssss", $ipaddress, $username, $password, $database, $ftplogin, $ftppassword);
				mysqli_stmt_execute($stmt);
			}
		}
		else
		if ($rowcount >= 1) {
			$id = 1;
			if ($stmt1 = mysqli_prepare($con, "UPDATE user_data SET host=?,username=?,password=?,dbname=?,ftplogin=?,ftppassword=? WHERE ID=?")) {
				mysqli_stmt_bind_param($stmt1, "ssssssi", $ipaddress, $username, $password, $database, $ftplogin, $ftppassword, $id);
				mysqli_stmt_execute($stmt1);
			}
		}

		$sqlquery1 = "SELECT * FROM user_data";
		$result1 = mysqli_query($con, $sqlquery1);
		if (mysqli_num_rows($result1) > 0) {

			// Details saved to the DB Success
            echo "<script>";
			echo "alert('message successfully sent')";
			echo "</script>";
			while ($row1 = mysqli_fetch_assoc($result1)) 
			{
				echo "uid: " . $row1["uid"] . " Host: " . $row1["host"] . " Username: " . $row1["username"] . "Password: " . $row1["password"] . "Database" . $row1["dbname"] . "FTP Login" . $row1["ftplogin"] . "FTP Password" . $row1["ftppassword"];

			}
		}
		else {
			echo "0 results";
		}
	}
}

function migrate_admin_page()
{


	if (($_POST['submit_btn'] == 'SYNC') && ($_POST['syncdb'] == 'Post')) {

		// print_r($_SESSION['POST']);

		//$h = $_SESSION['POST']['t1'];
		//$u = $_SESSION['POST']['t3'];
		//$p = $_SESSION['POST']['t4'];
		//global $wpdb;
		//$d = $wpdb->dbname;

		// echo DB_HOST;
		// echo DB_NAME;
		// echo DB_PASSWORD;
		// echo DB_USER;

		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

		// Check connection

		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}

		$sqlquery = "SELECT * FROM user_data";
		$result = mysqli_query($conn, $sqlquery);
		if (mysqli_num_rows($result) > 0) {

			// output data of each row

			while ($row = mysqli_fetch_assoc($result)) {
				$host = $row["host"];
				$username = $row["username"];
				$password = $row["password"];
				$database = $row["dbname"];
				$ftplogin = $row["ftplogin"];
				$ftppassword = $row["ftppassword"];
			}
		}
		else {
			echo "0 results";
		}

		echo $host."<br>";
		echo $username."<br>";
		echo $password."<br>";
		echo $database."<br>";
		echo $ftplogin."<br>";
		echo $ftppassword."<br>";
		
		$sqlq2="Select ID from wp_posts";
		$result2 = mysqli_query($conn, $sqlq2);
		if (mysqli_num_rows($result2) > 0) 
		{

			// output data of each row

			while ($row = mysqli_fetch_assoc($result2)) 
			{
				$did=$row['ID'];
			}
		}
		$did=$did+1;
		
	
		
		$con = mysqli_connect($host, $username, $password, $database);
		if (mysqli_connect_errno()) 
		{
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}
		$sqlq2="Select ID from wp_posts";
		$result2 = mysqli_query($conn, $sqlq2);
		if (mysqli_num_rows($result2) > 0) 
		{

			// output data of each row

			while ($row = mysqli_fetch_assoc($result2)) 
			{
				$pid=$row['ID'];
			}
		}
		$pid=$pid+1;

		if($did==$pid)
		{
		
		$queries = file_get_contents(WP_CONTENT_DIR . '/sql.log');
		$arr = explode("#@#", $queries);

		// $x=count($arr);

		foreach($arr as $key => $q) {

			// echo $q."</br>";

			mysqli_multi_query($con, $q);
		}

		file_put_contents(WP_CONTENT_DIR . '/sql.log', "");
		echo "DONE!";
		}
		else
		{
			echo "The ids are not in sync of wp_posts table.Please sync them first!!!";
		}
		mysqli_close($con);
	}
	else
	if (($_POST['submit_btn'] == 'SYNC') && ($_POST['syncdb'] == 'Plugins')) 
	{

		error_reporting(0);
		set_time_limit(0);
		ini_set("memory_limit", "999999M");
		//print_r($_SESSION['POST']);
		dir();
		$host = $_SESSION['POST']['t1'];
		$ip=gethostbyname($host);
		$ftplogin = $_SESSION['POST']['t5'];
		$ftppassword = $_SESSION['POST']['t6'];
		
		$ftp_conn = ftp_connect($ip, 21);
		$ftp_conn_login_result = ftp_login($ftp_conn, $ftplogin , $ftppassword );
		//echo "ftp_conn_login_result: ".$ftp_conn_login_result."<br>";
		$sourceDir = WP_CONTENT_DIR . '/plugins';
		$destDir = 'wp-content/plugins';
		//echo "sourceDir: ".$sourceDir."<br>";
		//echo "destDir: ".$destDir."<br>";
		
		$invalidFiles = array(
			'.',
			'..'
		);
        ftp_pasv($ftp_conn, true);
		$server_plugin_content = ftp_nlist($ftp_conn, $destDir);
		//print_r($server_plugin_content);
		            
					function ftp_putAll($ftp_conn,$sourcepath,$destpath)
					{
					//echo $sourcepath."<br />";
					$d=opendir($sourcepath);
					while($file = readdir($d))
					{
						 //echo $file."<br />";
						 if ($file != "." && $file != "..") 
						 {
					            		 
						      if(is_dir($sourcepath."/".$file))
						      {
								ftp_mkdir($ftp_conn,$destpath."/".$file);
							    ftp_putAll($ftp_conn,$sourcepath."/".$file,$destpath."/".$file);
						      }
						      else
						      {
							    ftp_put($ftp_conn,$destpath."/".$file,$sourcepath."/".$file,FTP_BINARY);
						      }
					      }
					}
					closedir($d);
					}
					
					function ftp_deleteAll($ftp_conn,$destpath)
					{
						
						//$d=ftp_nlist($ftp_conn,$destpath);
						//print_r($d);
					    //while(($file = $d[$i])&&($i<=count($d)))
					    //{
						    //echo $file."<br />";
							
						        $extdir = pathinfo($destpath, PATHINFO_EXTENSION);
								echo $destpath;
								$list =ftp_nlist($destpath);
								echo $list;
								$countfiles=count($list);
								echo $countfiles;
							    if(($extdir==""))
							    {
									echo "zzz";
								     ftp_rmdir($ftp_conn,$destpath);
									
							    }
							     else 
								   if(($extdir=="")&&((count(glob($destpath) > 0 ))))
						           {
									$listdir = ftp_nlist($destpath);
									print_r($listdir);
						           }
						        else
						        {
									echo "bb";
								    ftp_delete($destpath);
									//$listdir = ftp_nlist($destpath);
									//print_r($listdir);
									/*for ($i=0;$i<count($listdir);$i++)
									{
							           ftp_deleteAll($ftp_conn,$destpath."/".$listdir[$i]);
									}*/
						        }
					        
						
						
					    //}
				
					
						
					}
					
					
		$handle = opendir($sourceDir);
		while (($file = readdir($handle)) !== false) {
			if (in_array($file, $invalidFiles)) continue;

			//echo "file: ".$file."<br>";

			$sourcepath = $sourceDir . "/" . $file;
			//$sourcepath = $sourceDir . DIRECTORY_SEPARATOR . $file;
			//$destpath = $destDir . DIRECTORY_SEPARATOR . $file;
            $destpath = $destDir . "/" . $file;
			//echo "sourcepath: ".$sourcepath."<br>";
			//echo "destpath: ".$destpath."<br>";
            
			if (in_array($file, $server_plugin_content)) 
			{
				
				continue;
			}
			else 
            {
				//echo "s"."<br />";
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				//echo "<br />".$destpath;
				//echo "<br />".$sourcepath;
				if($ext=="")
				{
					if($file=="Migrate")
					{
						echo "MIGRATE PLUGIN WOULD NOT BE SYNCED";
					}
					else
					{
						
					ftp_mkdir($ftp_conn,$destpath);
					//echo $file."<br />";
					//echo $destpath."<br />";
					//echo $sourcepath."<br />";
					
					
					ftp_putAll($ftp_conn,$sourcepath,$destpath);
					
					}
					
				}
				else
				{
					if (ftp_put($ftp_conn, $destpath, $sourcepath, FTP_ASCII))
                     {
                       echo "Successfully uploaded $file.";
                     }
                    else
                     {
                      echo "Error uploading $file.";
                     }
				}
				/*if(is_file($file)) 
				{
					
					 //File upload code
					
					if (ftp_put($ftp_conn, $destpath, $sourcepath, FTP_ASCII))
                     {
                       echo "Successfully uploaded $file.";
                     }
                    else
                     {
                      echo "Error uploading $file.";
                     }
					
				} 
				else 
				{
					// Directory upload code
					foreach (glob($sourcepath."*") as $filename)
					{
                         ftp_put($ftp_conn, basename($filename) , $filename, FTP_BINARY);
					}
				}*/
			}
		}
       closedir($handle);
	   //print_r($server_plugin_content);
	   $local=array();
	   $handle = opendir($sourceDir);
		while (($file = readdir($handle)) !== false) 
		{
			array_push($local,$file);
		}
		//print_r($local);
		sort($local);
		//print_r($local);
		sort($server_plugin_content);
		//print_r($server_plugin_content);
		$sc=count($server_plugin_content);
		$lc=count($local);
		if($sc>$lc)
		{
			for($i=$lc;$i<$sc;$i++)
			{
				echo $server_plugin_content[$i]."<br>";
				echo $destDir."<br>";
				echo $destDir."/".$server_plugin_content[$i]."<br>";
			ftp_deleteAll($ftp_conn,$destDir."/".$server_plugin_content[$i]);
			}
		}
		/*for($i=0;$i<count($server_plugin_content);$i++)
		{
			
		}*/			
	   
	   
       	   
	}
	if (($_POST['submit_btn'] == 'SYNC') && ($_POST['syncdb'] == 'Images')) 
	{

        $host = $_SESSION['POST']['t1'];
		$ip=gethostbyname($host);
		$ftplogin = $_SESSION['POST']['t5'];
		$ftppassword = $_SESSION['POST']['t6']; 
		// print_r($_SESSION['POST']);
		$upload_dir = wp_upload_dir();
		
		$ftp_conn = ftp_connect($ip, 21);
		$ftp_conn_login_result = ftp_login($ftp_conn, $ftplogin, $ftppassword);
		echo "ftp_conn_login_result: ".$ftp_conn_login_result."<br>";
		$sourceDir = WP_CONTENT_DIR.'/uploads/2017/07';
		$destDir = 'wp-content/uploads/2017/07';
		$destnew = 'wp-content/uploads';
		$invalidFiles = array(
			'.',
			'..'
		);
		$fyear=date("Y");
		$fmonth=date("m");
        if (ftp_mkdir($ftp_conn, $destnew))
        {
          echo "Successfully created $destnew";
        }
        else
        {
           echo "Error while creating $destnew";
        }
		
		$destnew1=$destnew."/".$fyear;
		if (ftp_mkdir($ftp_conn, $destnew1))
        {
          echo "Successfully created $destnew1";
        }
        else
        {
           echo "Error while creating $destnew1";
        }
		$destnew2=$destnew1."/".$fmonth;
		if (ftp_mkdir($ftp_conn, $destnew2))
        {
          echo "Successfully created $destnew2";
        }
        else
        {
           echo "Error while creating $destnew2";
        }
		
        ftp_pasv($ftp_conn, true);
		$server_plugin_content = ftp_nlist($ftp_conn, $destDir);
		print_r($server_plugin_content);
        function ftp_putAll($ftp_conn,$sourcepath,$destpath)
					{
					//echo $sourcepath."<br />";
					$d=opendir($sourcepath);
					while($file = readdir($d))
					{
						 //echo $file."<br />";
						 if ($file != "." && $file != "..") 
						 {
					            		 
						      if(is_dir($sourcepath."/".$file))
						      {
								ftp_mkdir($ftp_conn,$destpath."/".$file);
							    ftp_putAll($ftp_conn,$sourcepath."/".$file,$destpath."/".$file);
						      }
						      else
						      {
							    ftp_put($ftp_conn,$destpath."/".$file,$sourcepath."/".$file,FTP_BINARY);
						      }
					      }
					}
					closedir($d);
					}		
        $handle = opendir($sourceDir);
		
		while (($file = readdir($handle)) !== false) 
		{
			if (in_array($file, $invalidFiles)) continue;
            echo "file: ".$file."<br>";

			$sourcepath = $sourceDir . "/" . $file;
			//$sourcepath = $sourceDir . DIRECTORY_SEPARATOR . $file;
			//$destpath = $destDir . DIRECTORY_SEPARATOR . $file;
            $destpath = $destDir . "/" . $file;
			//echo "sourcepath: ".$sourcepath."<br>";
			//echo "destpath: ".$destpath."<br>";
            
			if (in_array($file, $server_plugin_content)) 
			{
				
				continue;
			}
			else 
            {
				echo "s"."<br />";
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				//echo "<br />".$destpath;
				//echo "<br />".$sourcepath;
				if($ext=="")
				{
					ftp_mkdir($ftp_conn,$destpath);
					//echo $file."<br />";
					echo $destpath."<br />";
					echo $sourcepath."<br />";
					
					
					ftp_putAll($ftp_conn,$sourcepath,$destpath);
					
					
					
				}
				else
				{
					if (ftp_put($ftp_conn, $destpath, $sourcepath, FTP_BINARY))
                     {
                       echo "Successfully uploaded $file.";
                     }
                    else
                     {
                      echo "Error uploading $file.";
                     }
				}
			}
		}

		closedir($handle);
		
		
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

		// Check connection

		if (!$conn) 
		{
			die("Connection failed: " . mysqli_connect_error());
		}

		$sqlquery = "SELECT * FROM user_data";
		$result = mysqli_query($conn, $sqlquery);
		if (mysqli_num_rows($result) > 0) {

			// output data of each row

			while ($row = mysqli_fetch_assoc($result)) {
				$host = $row["host"];
				$username = $row["username"];
				$password = $row["password"];
				$database = $row["dbname"];
			}
		}
		else {
			echo "0 results";
		}

		echo $host;
		echo $username;
		echo $password;
		echo $database;
		$con = mysqli_connect($host, $username, $password, $database);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$queries = file_get_contents(WP_CONTENT_DIR . '/sql.log');
		$arr = explode("#@#", $queries);

		// $x=count($arr);

		foreach($arr as $key => $q) {

			// echo $q."</br>";

			mysqli_multi_query($con, $q);
		}

		file_put_contents(WP_CONTENT_DIR . '/sql.log', "");
		echo "DONE!";
		mysqli_close($con);
				            
		
	}
	else if(($_POST['submit_btn'] == 'SYNC') && ($_POST['syncdb'] == 'CSS'))
	{
		 $x=wp_get_theme();
		 $y= str_replace(' ', '', $x);
		 $y= strtolower($y);
		 //echo $x;
		 //echo $y;
		 $host = $_SESSION['POST']['t1'];
		 $ip=gethostbyname($host);
		 $ftplogin = $_SESSION['POST']['t5'];
		 $ftppassword = $_SESSION['POST']['t6'];
		 $username = $_SESSION['POST']['t3'];
		 $password = $_SESSION['POST']['t4'];
		 $database = $_SESSION['POST']['t2'];
		 $ftp_conn = ftp_connect($host, 21);
		 $ftp_conn_login_result = ftp_login($ftp_conn, $ftplogin, $ftppassword);
		 //echo "ftp_conn_login_result: ".$ftp_conn_login_result."<br>";
		 $sourceDir =WP_CONTENT_DIR."\\themes\\".$y."\\style.css";
		 
		 $destDir = "wp-content/themes/".$y;
		 $destDir1 = "wp-content/themes/".$y."/style.css";
		 //echo "sourceDir: ".$sourceDir."<br>";
		 //echo "destDir: ".$destDir."<br>";
		 
		 $con = mysqli_connect($host, $username, $password, $database);
		 if (mysqli_connect_errno()) 
		 {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		 }
		 
		 
		 $sqlq1="Select option_value from wp_options where option_name = 'template'";
		 $opname="template";
		 
		 $result1 = mysqli_query($con, $sqlq1);
		 
		 
		 if (mysqli_num_rows($result1) > 0) 
		 {
            // output data of each row
            while($row = mysqli_fetch_assoc($result1)) 
			 {
               //echo $row["option_id"]. "<br>";
			   //echo $row["option_name"]. "<br>";
			   $themename = $row["option_value"];
			   //echo $row["autoload"]. "<br>";
             }
         }
		 else 
		 {
           echo "0 results";
         }
		 
		 echo $themename;
		 if($themename==$y)
		 {

				
		             if (ftp_delete($ftp_conn,$destDir1 )) 
					 {
                       echo "deleted successful\n";
                     } 
					 else 
					 {
                       echo "could not delete \n";
                     }
					 ftp_pasv($ftp_conn, true);
                     //ftp_chdir($ftp_conn,$destDir );		

					  //chmod($sourceDir,0777);
		             //$data=file_get_contents($sourceDir);
                     //echo $data;

					 //die();
		             if (ftp_put( $ftp_conn, $destDir1, $sourceDir, FTP_ASCII))
                     {
                       echo "Successfully uploaded";
                     }
                     else
                     {
                      echo "Error uploading";
                     }
		
		 }
		 else
		 {
			 echo "The Themes don't match";
		 }	 
		
		
	}
	else if(($_POST['submit_btn'] == 'SYNC') && ($_POST['syncdb'] == 'HTML'))
	{
		
		set_time_limit(0);
		$host = $_SESSION['POST']['t1'];
		$ip=gethostbyname($host);
		$ftplogin = $_SESSION['POST']['t5'];
		$ftppassword = $_SESSION['POST']['t6'];
		$username = $_SESSION['POST']['t3'];
		$password = $_SESSION['POST']['t4'];
		$database = $_SESSION['POST']['t2'];
		$ftp_conn = ftp_connect($host, 21);
		$ftp_conn_login_result = ftp_login($ftp_conn, $ftplogin, $ftppassword);
		echo "ftp_conn_login_result: ".$ftp_conn_login_result."<br>";
		$x=wp_get_theme();
		$y= str_replace(' ', '', $x);
		$y= strtolower($y);
		//$sourceDir="C:\\xampp\\htdocs\\local\\wp-content\\themes\\twentyseventeen";
		$u=get_home_path();
		$w=explode("/",$u);
		$q=implode("\\",$w);
		//echo $u;
		$sourceDir=$q."wp-content\\themes\\".$y;
		echo $sourceDir;
		echo "<br>";
		$destinationDir="wp-content/themes/".$y;
		echo $destinationDir;
		function ftp_AddAll($ftp_conn,$sourcepath,$destpath)
					{
					echo $sourcepath."<br />";
					$d=opendir($sourcepath);
					while($file = readdir($d))
					{
						 echo $file."<br />";
						 if ($file != "." && $file != "..") 
						 {
					            		 
						      if(is_dir($sourcepath."\\".$file))
						      {
								//ftp_mkdir($ftp_conn,$destpath."/".$file);
							    ftp_AddAll($ftp_conn,$sourcepath."\\".$file,$destpath."/".$file);
						      }
						      else
						      {
								
								if(ftp_delete($ftp_conn,$destpath."/".$file))
								{
									echo "yes";
								}									
                                else
								{
									echo "no";
								}									
								if(ftp_put($ftp_conn,$destpath."/".$file,$sourcepath."\\".$file,FTP_BINARY))
								{
									echo "yes";
								}
								else
								{
									echo "no";
								}
						      }
					      }
					}
					closedir($d);
					}
					
					$con = mysqli_connect($host, $username, $password, $database);
		            if (mysqli_connect_errno()) 
		            {
			           echo "Failed to connect to MySQL: " . mysqli_connect_error();
		            }
		 
		 
		            $sqlq1="Select option_value from wp_options where option_name = 'template'";
		 
		 
		            $result1 = mysqli_query($con, $sqlq1);
		 
		 
		            if (mysqli_num_rows($result1) > 0) 
		            {
                     // output data of each row
                     while($row = mysqli_fetch_assoc($result1)) 
			          {
                         //echo $row["option_id"]. "<br>";
			             //echo $row["option_name"]. "<br>";
			             $themename = $row["option_value"];
			             //echo $row["autoload"]. "<br>";
                      }
                    }
		            else 
		            {
                       echo "0 results";
                    }
		 
		echo $themename;
		if($themename==$y)
		{			

		$d=opendir($sourceDir);
		while((($file = readdir($d)) !== false))
		{
			if ($file != "." && $file != "..") 
			{
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if($ext=="php")
				{
					 echo $file."<br  />";
					 if (ftp_delete($ftp_conn,$destinationDir."//".$file )) 
					 {
                       echo "deleted successful\n";
                     } 
					 else 
					 {
                       echo "could not delete \n";
                     }
					 ftp_pasv($ftp_conn, true);
					 //ftp_chdir($ftp_conn, 'wp-content/themes/twentyseventeen/');
					 
					 if (ftp_put( $ftp_conn, $destinationDir.'/'.$file, $sourceDir."\\".$file, FTP_ASCII))
                     {
                       echo "Successfully uploaded";
                     }
                     else
                     {
                       echo "Error uploading";
                     }
		        }
			else
			if($ext=="")
			{
					ftp_AddAll($ftp_conn,$sourceDir,$destinationDir);	
			}
	
				
			}
		}
		}
		else
		{
			echo "The Themes are different";
		}
		
	}

?>
	<div class="wrap">
		<h2>Welcome To My Plugin</h2>
	</div>
	<form action="" method="POST">
		<input type="checkbox" name="syncdb" value="CSS">CSS<br />
		<input type="checkbox" name="syncdb" value="HTML">HTML<br />
		<input type="checkbox" name="syncdb" value="Plugins">PLUGINS<br />
		<input type="checkbox" name="syncdb" value="Images">IMAGES<br />
		<input type="checkbox" name="syncdb" value="Post">POSTS<br />
		<input type="submit" value="SYNC" name="submit_btn">
	</form>
	
<?php
}

?>
