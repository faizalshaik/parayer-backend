<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class Prayer_api extends CI_Controller {
	public function __construct(){
		parent::__construct();
		date_default_timezone_set('America/Chicago');
		$this->load->model('User_model');
		$this->load->model('Base_model');

		$this->tblPrayer = 'tbl_prayer';
		$this->tblVideo = 'tbl_video';
		$this->tblSetting = "tbl_setting";
	}

	public function index()
	{
		echo "index";
	}

	public function get_videos()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if($userInfo==null) return;

		$videos = $this->Base_model->getDatas($this->tblVideo, null);
		$links = [];
		foreach($videos as $video)
			$links[]=$video->link;

		$this->reply(200, 'ok', $links);
	}


	public function gets()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if($userInfo==null) return;

		$prayers = $this->Base_model->getDatas($this->tblPrayer, ['user_id'=>$userInfo->Id]);
		$this->reply(200, 'ok', $prayers);
	}

	public function buy_seed()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);		
		if($userInfo==null)return;

		$prayer = $request['prayer'];
		if($prayer=='')
			return $this->reply(400, 'missed prayer', null);

		$id = $this->Base_model->insertData($this->tblPrayer,
				['user_id'=>$userInfo->Id, 'prayer'=>$prayer, 
				 'step'=>1,
				 'registered_dt'=>date('Y-m-d'),
				 'updated_dt'=>date('Y-m-d')
				]
			);
		$prayer = $this->Base_model->getRow($this->tblPrayer, ['Id'=>$id]);
		$this->reply(200, 'ok', $prayer);
	}

	public function grow()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);		
		if($userInfo==null)return;

		$Id = $request['Id'];
		if($Id=='' || $Id==0)
			return $this->reply(400, 'missed Id', null);

		$bible = $request['bible'];	
		if($bible=='')
			return $this->reply(400, 'missed bible', null);
		
		$prayer = $this->Base_model->getRow($this->tblPrayer, ['Id'=>$Id]);
		if($prayer == null)
			return $this->reply(400, 'invalid Id', null);
		
		if($prayer->step>=6)
			return $this->reply(400, 'Already full grown', null);

		$prayer->step = $prayer->step+1;
		$this->Base_model->updateData($this->tblPrayer, ['Id'=>$Id], 
			['step'=>$prayer->step, 'bible_'.$prayer->step => $bible, 'updated_dt'=>date('Y-m-d')]
		);

		$prayer = $this->Base_model->getRow($this->tblPrayer, ['Id'=>$Id]);		
		$this->reply(200, 'ok', $prayer);
	}
	

	public function get_home_contents()
	{
		$rows = $this->Base_model->getDatas($this->tblSetting, null);
		$data = [];
		foreach($rows as $itm)
		{
			$data[$itm->name] = $itm->value;
		}	
		$this->reply(200, 'ok', $data);
	}		

}