<?

/* Writed by İnan YAZILI - 08.05.2006 */

/* header("Content-Type: text/html; charset=UTF-8");  */

class mysqlConnect{

	public	$db;
	private $vt=false;
	private $result;
	public $postQuery = array();

	function __construct($connectArray)
	{
		$this->host		= $connectArray["HOST"];
		$this->username = $connectArray["USERNAME"];
		$this->password = $connectArray["PASSWORD"];
		$this->database	= $connectArray["DB"];
	}

	public function connectDB()
	{

		try
		{
			$this->vt = mysqli_connect($this->host,$this->username,$this->password,$this->database);

			if (mysqli_connect_errno())
			{
				throw new Exception ('Hata: Veritabani Baglantisi Kurulamadi');
			}

			$this->db =  mysqli_select_db($this->vt,$this->database);

			if (!$this->db )
			{
				throw new Exception ('Hata: Veritabanı Secilemedi');
			}

			mysqli_query($this->vt,"SET NAMES 'UTF8'");
			mysqli_query($this->vt,"SET CHARACTER SET 'UTF8'");

		}
		catch (Exception $e)
		{
			die($e->getMessage());
			exit;
		}
	}

	// List mysql functions
	public function fetchAll(){return(mysqli_fetch_all($this->result));}

	public function fetchField(){return(mysqli_fetch_field($this->result));}

	public function fetchAssoc(){return(mysqli_fetch_assoc($this->result));}

	public function fetchArray(){return(mysqli_fetch_array($this->result));}

	public function fetchObject(){return(mysqli_fetch_object($this->result));}

	public function numRows(){return(mysqli_num_rows($this->result));}

	public function affectedRows(){return(mysqli_affected_rows($this->vt));}

	public function fetchRow(){return(mysqli_fetch_row($this->result));}

	public function freeResult(){return(mysqli_free_result($this->result));}

	public function insertId(){return(mysqli_insert_id($this->vt));}

	public function errorDetail(){if($this->vt && mysqli_errno($this->vt)) return mysqli_error($this->vt); else return "";}

	public function storeResult(){return(mysqli_store_result($this->vt));}

	public function useResult(){return(mysqli_use_result($this->vt));}

	public function moreResult(){return(mysqli_more_results($this->vt));}

	public function nextResult(){return(mysqli_next_result($this->vt));}

	public function close(){return(mysqli_close($this->vt));}

	public function listTables(){

		$this->query("SHOW TABLES");
		$numRows =  $this->numRows();

		for($i=0;$i<$numRows;$i++)
		{
			$selectTables[] = $this->fetchArray();
		}

		foreach($selectTables as $val)
		{
			$result[] =  $val[0];
		}
		return $result;

	}

	public function query($query)
	{
		$this->vt or $this->connectDB();

		try
		{
			$this->result = @ mysqli_query($this->vt,$query);

			if (!$this->result)
			{
				throw new Exception ("<h2><div>Error(s) occured.</div></h2>".$this->errorDetail()."<br />\n Query Error:<br />\n<pre>".$query."</pre>"); exit;
			}
			else
			{
				$this->postQuery["SOLO"][] = $query;
				return true;
			}
		}
		catch (Exception $e)
		{
			print_r(str_replace(PHP_EOL,"<br>",$e->getMessage()));
			return false;
		}
	}

	public function multiQuery($query)
	{

		$this->vt or $this->connectDB();

		try
		{

			$this->result = mysqli_multi_query($this->vt,$query);

			if(!$this->result)
			{
				throw new Exception ($this->errorDetail());
				exit;
			}
			else
			{
				$this->postQuery["MULTI"][]  = $query;
				return true;
			}
		}
		catch (Exception $e)
		{
			print_r(str_replace(PHP_EOL,"<br>",$e->getMessage().' - '.$query));
			return false;
		}
	}

	public function multiList($name='')
	{

		$log			= array();
		$queryCount		= 0;
		$getResult		= array();
		do {
			if($this->result = $this->useResult())
			{
				foreach ($this->result as $key=>$val )
				{
					if(is_array($name))
					{
						$arrName = (isset($name[$queryCount])) ? $name[$queryCount] : $queryCount;

						$getResult[$arrName][] = $val;
					}
					else
					{
					    $getResult[$queryCount][] = $val;
					}
				}

				$this->freeResult();



				if($this->moreResult()){$queryCount++;}

			}
		} while ($this->moreResult() && $this->nextResult());

		return $getResult;

	}

	public function qSingle($sql)
	{
		$result = array();
		if ($this->query($sql))
		{
			reset($result);
			if($this->numRows()>0)
			{
				$data = $this->fetchObject();
				while($value = $this->fetchField())
				{
					$field = $value->name;
					$result[$field] = $data->$field;
				}
				unset($field,$data);
			}
			else
			{
				//return false;
				return $result;
			}
		}else{
			//return false;
			return $result;
		}

		$this->freeResult();
		return $result;

	}

	public function qCount($sql)
	{
		if ($this->query($sql))
		{
			if ($this->numRows()>0)
			{
				return $this->numRows();
			}
			else
			{
				return 0;
			}
		}
		else
		{
			return 0;
		}

	}

	public function qForeach($key=''){

		if($this->result)
		{

			$mainArray	= array();
			$subArray	= array();
			$fieldName	= array();
			$dataCount	= $this->numRows();

			if ($dataCount>0)
			{
				while ($value = $this->fetchField())
				{
					$fieldName[] = $value->name;
				}

				for ($i=0; $i<$dataCount; $i++)
				{
					$data  = $this->fetchObject();
					foreach($fieldName as $value)
					{
						$subArray[$value] = $data->$value;

						if ($key)
						{
							$mainArray[$data->$key] = $subArray;
						}
						else
						{
							$mainArray[] = $subArray;
						}
					}
				}

				unset($fieldName,$value,$data,$subArray);
			}
		}
		else
		{
			return false;
		}

		$this->freeResult();

		return $mainArray;

	}

	public function qMuch($sql,$key='')
	{
		if ($this->query($sql))
		{
			$mainArray	= array();
			$subArray	= array();
			$fieldName	= array();
			$dataCount	= $this->numRows();

			if ($dataCount>0)
			{
				while ($value = $this->fetchField())
				{
					$fieldName[] = $value->name;
				}

				for ($i=0; $i<$dataCount; $i++)
				{
					$data  = $this->fetchObject();
					foreach($fieldName as $value)
					{
						$subArray[$value] = $data->$value;
					}
					if ($key)
					{
						$mainArray[$data->$key] = $subArray;
					}
					else
					{
						$mainArray[] = $subArray;
					}
				}

				unset($fieldName,$value,$data,$subArray);
			}
		}
		else
		{
			return false;
		}
		$this->freeResult();

		return $mainArray;
	}

	public function total($sql)
	{
		$this->query($sql);
		list($this->qtotal) = mysqli_fetch_row($this->result);
		mysqli_free_result($this->result);
		return $this->qtotal;

	}

	//$input = isset($_POST["escapeString"]) ? $db->escapeString($_POST["escapeString"]) : false;
	public function escapeString($data)
	{
		$data = mysqli_real_escape_string($this->vt, $data);
		return $data;
	}

	public function showQuery()
	{
		return json_encode($this->postQuery);
	}

	function __destruct()
	{
		if($this->vt) return $this->close();
	}

}

?>
