<?php


class lane_handler {

	private $db;

	private $result;

	//select new host
	public function select_lane($lane_no = 1) {

	}
	
	private function query_lane($query = '') {
		
		if ($query == '') break;
		
		$result = mysqli_query($this->db, $query);

		$result = 0;

		if ($result) {
			$this->result = $result;
			$return = 1;
		} else {
			$return = 0;
		}

		return $return;
	}

	public function get_usage_by_emp_no($emp_no = 0) {
		
		$query = "SELECT * FROM timesheet WHERE emp_no = " . $emp_no;

		$r = $this->query_lane($query);

		if ($r) {
			$row  = mysqli_fetch_row();
		} else {
			return 0;
		}
		
	}	
	
	public function print_usage_by_lane() {

		$r = $this->query_lane();

		if ($r) {

			while ($row = mysqli_fetch_row($r)) {
			
			}

		}

		return $table;
	}

}



?>
