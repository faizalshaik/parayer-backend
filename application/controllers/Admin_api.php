<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class Admin_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('America/Chicago');
		$this->load->model('User_model');
		$this->load->model('Base_model');

		$this->tblPrayer = 'tbl_prayer';
		$this->tblVideo = 'tbl_video';
		$this->tblImage = 'tbl_image';
		$this->tblSetting = 'tbl_setting';
	}

	public function get_users()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$data = array();
		$rows = $this->User_model->getDatas(null, 'created_at');
		foreach ($rows as $item) {
			$row = [];
			$row['Id'] =  $item->Id;
			$row['name'] =  $item->name;
			$row['email'] =  $item->email;
			$row['created_at'] =  $item->created_at;

			$totalPrayer = $this->Base_model->getCounts('tbl_prayer', ['user_id' => $item->Id]);
			$fullGrown = $this->Base_model->getCounts('tbl_prayer', ['user_id' => $item->Id, 'step' => 6]);

			$row['total_prayers'] =  $totalPrayer;
			$row['full_growns'] =  $fullGrown;
			$row['in_growings'] =  $totalPrayer - $fullGrown;
			$data[] = $row;
		}

		$this->reply(200, 'ok', $data);
	}
	
	public function get_images()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$images = $this->Base_model->getDatas($this->tblImage, null);
		$data = [];
		foreach($images as $img)
		{
			$row = ['Id'=>$img->Id];
			$row['link'] = base_url($img->link);
			$data[] = $row;
		}		
		$this->reply(200, 'ok', $data);
	}


	private function saveImage($imgString)
	{
		$idx = strpos($imgString, ',');
		if($idx <0)return '';
		$headerStr = substr ( $imgString , 0, $idx );

		$idx1 = strpos($headerStr, '/');
		$idx2 = strpos($headerStr, ';');
		if($idx1 <0 || $idx2 <0) return '';
		$ext = substr($headerStr, $idx1+1, $idx2 - $idx1-1);

		$tmpfileName = time().'.'.$ext; 
		if(!is_dir("uploads/image")) {
			mkdir("uploads/image/");
		}

		$filePath = 'uploads/image/'.$tmpfileName;
		$myfile = fopen($filePath, "w");
		fwrite( $myfile, base64_decode( substr ( $imgString , $idx+1 ) ));
		fclose( $myfile );
		return $filePath;
	}	


	public function put_image()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);
			
		$image = $request['image'];
		if($image=="")
			return $this->reply(401, 'missed image!', null);
		$link = $this->saveImage($image);
		$Id = $this->Base_model->insertData($this->tblImage, ['link'=>$link]);
		$this->reply(200, 'ok', ['Id'=>$Id, 'link'=>base_url($link)]);
	}

	public function remove_image()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);
			
		$Id = $request['Id'];
		if($Id=="" || $Id <=0)
			return $this->reply(401, 'missed Id!', null);

		$image = $this->Base_model->getRow($this->tblImage, ['Id'=>$Id]);
		if($image!=null)
		{
			if(file_exists($image->link))
				unlink($image->link);
		}
		$this->Base_model->deleteRow($this->tblImage, ['Id'=>$Id]);
		$this->reply(200, 'ok', null);
	}

	

	public function get_videos()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$videos = $this->Base_model->getDatas($this->tblVideo, null);
		$this->reply(200, 'ok', $videos);
	}

	public function add_new_video()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$link = $request['link'];
		if ($link == '')
			return  $this->reply(400, 'missed link param', null);
		$Id = $this->Base_model->insertData($this->tblVideo, ['link' => $link]);
		$this->reply(200, 'ok', ['Id' => $Id, 'link' => $link]);
	}

	public function update_video()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$Id = $request['Id'];
		if ($Id == '' || $Id <= 0)
			return  $this->reply(400, 'missed Id param', null);

		$link = $request['link'];
		if ($link == '')
			return  $this->reply(400, 'missed link param', null);

		$Id = $this->Base_model->updateData($this->tblVideo, ['Id' => $Id], ['link' => $link]);
		$this->reply(200, 'ok', null);
	}

	public function remove_video()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$Id = $request['Id'];
		if ($Id == '' || $Id <= 0)
			return  $this->reply(400, 'missed Id param', null);

		$Id = $this->Base_model->deleteRow($this->tblVideo, ['Id' => $Id]);
		$this->reply(200, 'ok', null);
	}


	public function get_home_contents()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);


		$rows = $this->Base_model->getDatas($this->tblSetting, null);
		$data = [];
		foreach ($rows as $itm) {
			$data[$itm->name] = $itm->value;
		}
		$this->reply(200, 'ok', $data);
	}


	public function put_home_contents()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->type != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$contents = $request['contents'];
		foreach ($contents as $content) {
			$this->Base_model->updateData($this->tblSetting, ['name' => $content['name']], ['value' => $content['value']]);
		}
		$this->reply(200, 'ok', null);
	}
}
