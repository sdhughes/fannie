<?php 

require_once('../config.php');
if (defined(IS4C_OPDATA)) echo IS4C_OPDATA;



	/*****
	 * class to handle the db connection and hide it a bit
	 *
	 *
	 *
	 ********/
	class db_handler {


		private $conn;
		private $title = '';


		/*****
		 * 
		 ****/
		public function init() {
			$this->openDB();
			$this->title = 'Duplicate Record Handler';
		}
		/*****
		 * 
		 ****/
		public function destroy() {

			$this->closeDB();

		}

		/*****
		 * 
		 ****/
		private function openDB() {
	
			$this->conn = $db = mysqli_connect(IS4C_HOST,IS4C_USER,IS4C_PASS);

		}

		/*****
		 * 
		 ****/
		public function selectDB($db) {
			switch ($db) {
				case 'OP':
					$selection  = IS4C_OPDATA;
					break;
				case 'LOG':
					$selection  = IS4C_LOG;
					break;
				default:
					$selection = 'comet';
			}

			mysqli_select_db($this->conn,$selection) or die ("DB select error: " . mysqli_error($this->conn));
		}

		/*****
		 * 
		 ****/
		private function closeDB() {
			mysqli_close($this->conn);
		}


		/*****
		 * 
		 ****/
		public function printHeader() {
			echo "	<html>
		<head>
			<title>$this->title</title>
			<link href='./style.css'/>
			<script type='text/javascript' src='../includes/javascript/jquery.js' ></script>
".//			<script type='text/javascript' src='./manageCustdata.js' ></script>
"			<script type='text/css' src='' ></script>

		</head>
		<body>";

		if (isset($_POST['message'])) {
			echo "<h3>" . $_POST['message'] . "</h3>";
		}

			}

		/*****
		 * 
		 ****/
		public function printFooter() {
			echo "
		</body>
	</html>";

		}

		/*****
		 * 
		 ****/
		public function displayCardsWithDupes() {
			
			$query = "SELECT * FROM (SELECT cardNo, count(cardNo) AS cnt, modified, memtype FROM custdata GROUP BY cardNo,personNum) AS spanned WHERE cnt > 1 and memtype IN (1,2,4,5)";

			$result = mysqli_query($this->conn,$query) or die(mysqli_error($this->conn));

			if ($result) { 
			
				$num_rows = mysqli_num_rows($result);

				echo "<p>$num_rows records with duplicates</p>";
echo "<form action=" . $_SERVER['PHP_SELF'] . " method='post'>";
				echo "<table>";
				echo "<th>Card No</th><th>Repeated</th><th>Date Modifed</th>";
				while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {

					$card_no = $row['cardNo'];
					$count = $row['cnt'];
					$modified = $row['modified'];
					echo "<tr><td class='cardNo'>$card_no</td><td>$count</td><td>$modified</td><td><input type='submit' name='delete' class='deleteDupe' value='delete$card_no' /></td></tr>";

				}
				echo "</table>";
				echo "</form>";
			}
		}


		/*****
		 *
		 *
		 *
		 *
		 *
		 *******/
		public function displayDupeRecords($cardNo) {

			$query = "SELECT cardNo, firstName,lastName,personNum, modified FROM custdata WHERE cardNo = $cardNo";


			echo "<br / >" . $query  . "<br />";
			$result = mysqli_query($this->conn,$query) or die(mysqli_error($this->conn));

			if ($result) { 
			
				$num_rows = mysqli_num_rows($result);

				echo "<p>$num_rows duplicates</p>";
echo "<form action=" . $_SERVER['PHP_SELF'] . " method='post'>";
				echo "<input type='hidden' name='cardNo' value='$cardNo' />";

				echo "<table>";
				echo "<th>Card No</th><th>First Name</th><th>Last Name</th><th>Date Modifed</th>";
				while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {

					$card_no = $row['cardNo'];
					$firstName = $row['firstName'];
					$lastName = $row['lastName'];
					$modified = $row['modified'];
					echo "<tr><td>$card_no</td><td>$firstName</td><td>$lastName</td><td><input type='hidden' name='modified[]' value='" . $row['modified']. "' />$modified</td></tr>";

				}
				echo "</table>";

				echo "<input type='submit' name='submit' value='delete dupes'/>";
				echo "<input type='hidden' name='message' value='Showing Duplicate Records' />";
				echo "</form>";
			}


		}



		/*****
		 *	deletes all records not modified when the last one was modified. 
		 ****/
		public function deleteEarliestDuplicate($cardNo, $cutOffDateTime) {

			$query = "DELETE FROM custdata WHERE cardNo = $cardNo and modified < '$cutOffDateTime'";
			echo $query;
			$result = mysqli_query($this->conn, $query) or die("Query error: " . mysqli_error($this->conn));
			if ($result == 1) {
				//echo "<input type='hidden' name='message' value='$cardNo before $cutOffDate deleted!' /></p>";
				echo "$cardNo before $cutOffDate deleted!' ";
				//header($_SERVER['PHP_SELF']);
			} else {
				echo "<input type='hidden' name='message' value='something went wrong.' />";
			}
		}
	}//end class db_handler


?>
