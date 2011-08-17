<?php

// IPLog functions
// $Id: iplog.inc.php 5 2009-12-21 01:36:19Z andys $

// We need ADODb
require('adodb/adodb.inc.php');
// ...and Smarty
require('smarty/libs/Smarty.class.php');

class IPLog
{
	// Database connection variables
	public $db_host;
	public $db_user;
	public $db_pass;
	public $db_name;

	// Set to 'true' to enable debugging
	public $db_debug;
	// Initialise variable for the database handle
	public $db_handle;
	// Initialise array for prepared SQL procedures
	public $db_pq		= array();

	// Directory settings
	public $web_root;
	public $web_stpl;
	public $web_scmp;
	public $web_scac;
	public $web_scnf;

	// Display variables
	public $p;

	// Version
	const iplog_ver	= "0.001";

	// Constructor
	public function __construct()
	{
		if(!$this->_LoadConfig())
		{
			return false;
		}
		if(!$this->_DBConnect())
		{
			return false;
		}
		if(!$this->_PageInit())
		{
			return false;
		}

		return true;
	}

	private function _LoadConfig()
	{
		$x = simplexml_load_file('./config.xml');

		$this->db_host	= $x->db[0]->host;
		$this->db_user	= $x->db[0]->user;
		$this->db_pass	= $x->db[0]->pass;
		$this->db_name	= $x->db[0]->name;
		if($x->db[0]->debug != "")
		{
			$this->db_debug	= $x->db[0]->debug;
		}

		$this->web_root	= $x->path[0]->root;
		$this->web_stpl	= $this->web_root.$x->path[0]->smarty[0]->template;
		$this->web_scmp	= $this->web_root.$x->path[0]->smarty[0]->compile;
		$this->web_scac	= $this->web_root.$x->path[0]->smarty[0]->cache;
		$this->web_scnf	= $this->web_root.$x->path[0]->smarty[0]->config;

		return true;
	}

	// Database functions
	private function _DBConnect()
	{
		// Connect to DB
		$conn = &ADONewConnection('mysql');
		$conn->PConnect($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
		$conn->debug = $this->db_debug;

		if($conn->IsConnected())
		{
			$this->db_handle = &$conn;
			// Prepare DB queries
			$this->_DBPrepareQueries();
			return true;
		}
		else
		{
			return false;
		}
	}

	private function _DBDisconnect()
	{
		if($this->db_handle)
		{
			unset($this->db_handle);
			return true;
		}
		else
		{
			return false;
		}
	}

	// Prepare DB queries
	private function _DBPrepareQueries()
	{
		if(!$this->db_handle)
		{
			return false;
		}

		// Full results searches
		$this->db_pq['get_all_sources']				= $this->db_handle->Prepare('SELECT * FROM source');
		$this->db_pq['get_all_chains']				= $this->db_handle->Prepare('SELECT * FROM chain');
		$this->db_pq['get_all_ips']					= $this->db_handle->Prepare('SELECT * FROM ip');

		// Source queries
		$this->db_pq['get_source_id_by_ip']			= $this->db_handle->Prepare('SELECT id FROM source WHERE ip = ?');
		$this->db_pq['get_source_ip_by_id']			= $this->db_handle->Prepare('SELECT ip FROM source WHERE id = ?');

		// Chain queries
		$this->db_pq['get_chain_namesrc_by_id']		= $this->db_handle->Prepare('SELECT name, source_id FROM chain WHERE id = ?');
		$this->db_pq['get_chain_id_by_namesrc']		= $this->db_handle->Prepare('SELECT id FROM chain WHERE name = ? AND source_id = ?');
		$this->db_pq['get_chain_ids_by_src']		= $this->db_handle->Prepare('SELECT id FROM chain WHERE source_id = ?');

		return true;

	}

	private function _DBExecutePreparedQuery($query_name, $args)
	{
		if(!$this->db_handle)
		{
			return false;
		}

		global $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$rs = $this->db_handle->Execute($this->db_pq[$query_name], $args);

		if($rs->_numOfRows > 0)
		{
			$output['results'] = $rs->GetArray();
			return $output;
		}
		else
		{
			return false;
		}
	}

	public function GetAllIPs()
	{
		return $this->_DBExecutePreparedQuery("get_all_ips", '');
	}

	// Get source ID by IP address
	// See also: GetSourceIPByID
	public function GetSourceIDByIP($args)
	{
		return $this->_DBExecutePreparedQuery("get_source_id_by_ip", $args);
	}

	public function GetSourceIPByID($args)
	{
		return $this->_DBExecutePreparedQuery("get_source_ip_by_id", $args);
	}

	public function GetChainNameAndSourceByID($args)
	{
		return $this->_DBExecutePreparedQuery("get_chain_namesrc_by_id", $args);
	}

	public function GetChainIDByNameAndSource($args)
	{
		return $this->_DBExecutePreparedQuery("get_chain_id_by_namesrc", $args);
	}

	// Display functions

	private function _PageInit()
	{
		$s = new Smarty();

		$s->template_dir	= $this->web_stpl;
		$s->compile_dir		= $this->web_scmp;
		$s->cache_dir		= $this->web_scac;
		$s->config_dir		= $this->web_scnf;

		// Misc page settings
		$s->assign('page_title', 'IPLog '.IPLog::iplog_ver);

		$this->p = $s;

		return;
	}

	public function PageDisplay($name)
	{
		if(!$this->p)
		{
			return false;
		}

		$this->p->display($name.'.tpl');

		return true;
	}

	public function PageDisplayTable($
//	private function _PageFillSmartyArray($name, 

}
		
