<?php

	ini_set("precision", 20);
	ini_set('post_max_size', '64M');
	ini_set('upload_max_filesize', '64M');
	set_time_limit(30000);

	if(!isset($_POST["source"])){

		//IF NO FILE UPLOAD IS PRESENT SHOW THE UPLOAD FORM
?>

<html>
	<body>
		<form action="index.php" method="post" enctype="multipart/form-data">
			<label for="file">File:</label>
			<input type="file" name="file" id="file"><br>
			<p>Specify the source of your data:</p>
			<label for="source_fao">FAOSTAT (faostat3.fao.org):</label>
			<input type="radio" name="source" id="source_fao" value="fao"><br>
			<label for="source_eurostat">EUROSTAT (appsso.eurostat.ec.europa.eu):</label>
			<input type="radio" name="source" id="source_eurostat" value="eurostat"><br>
			<input type="submit" name="submit" value="Submit">
		</form>
	</body>
</html>

<?php
	}else{
		if ($_FILES["file"]["error"] > 0) {
			echo "Error: " . $_FILES["file"]["error"];
			exit;
		} else {

			//FILE UPLOAD SUCCESSFUL
			if(($_FILES["file"]["type"] != "text/csv")&&($_FILES["file"]["type"] != "application/csv")){
				echo "File must be in Format text/csv";
				exit;
			}

			if(!isset($_POST["source"])){
				echo "Please define source";
				exit;	
			}

			header('Content-Type: text/csv');
			$name = explode(".", $_FILES["file"]["name"]);
			header('Content-Disposition: attachment; filename='.$name[0].'_clean.'.$name[1]);
			header('Pragma: no-cache');

			$string = "";
			$fhandle = fopen($_FILES["file"]["tmp_name"], "r");
			if ($fhandle) {
				$firstline = true;
				while (($row = fgets($fhandle)) !== false) {

					if($_POST["source"] == "fao"){
						if($firstline){
							$string .= $row;
							$firstline = false;
						}else{

							if((strlen($row)<1)||(substr($row, 0, strlen("FAOSTAT"))=="FAOSTAT")){
								//Ignore empty lines and the FAOSTAT meta line
							}else{

								$cols = explode('","', $row);
								$firstcol = true;
								foreach ($cols as $col) {
									if(!$firstcol){
										$string .= ",";
									}

									//Replace empty values with zeros
									if((strlen($col)<1)||($col=="na")||($col=="NA")||($col=="n/a")||($col=="N/A")){
										$col = "0";
									}

									//For some weird reason RAW sometimes doesn't like " or ' ?!
									$string .= str_replace(array('"', ','), '', $col);
									$firstcol = false;
								}

							}
						}

					}elseif($_POST["source"] == "eurostat"){

						if(strlen($row)<1){
							//Ignore empty lines and the FAOSTAT meta line
						}else{

							$cols = explode('","', $row);
							$firstcol = true;
							foreach ($cols as $col) {
								if(!$firstcol){
									$string .= ",";
								}

								//Convert 2014M01 INTO 2014-01-01
								//Convert 2014Q1 INTO 2014-01-01
								if(is_numeric(substr($col, 0, 4))){
									if(substr($col, 4, 1)=="M"){
										$col = substr($col, 4, 1).'-'.substr($col, 5).'-01 00:00:00';
									}elseif(substr($col, 4, 1)=="Q"){
										$col = substr($col, 4, 1).'-'.(((intval(substr($col, 5))-1)*3)+1).'-01 00:00:00';
									}
								}

								//Replace empty values with zeros
								if((strlen($col)<1)||($col==":")||($col=="na")||($col=="NA")||($col=="n/a")||($col=="N/A")){
									$col = "0";
								}

								//Convert 1,234,567.8 to 1234567.8
								if(is_numeric(str_replace(",", "", $col))){
									$col = str_replace(",", "", $col);
								}
							}
						}

					}

				}
			}
		
			echo $string;
		}
	}

?>