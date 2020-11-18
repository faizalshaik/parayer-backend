<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class Auth_api extends CI_Controller {
	public function __construct(){
		parent::__construct();
		date_default_timezone_set('America/Chicago');

		$this->load->helper('url');

		$this->load->model('User_model');
		$this->tokenDuration = 3600;		

		//for attorney
		$this->siteDomain = 'attorney.bio';
		$this->siteName = 'attorney.BIO';
		$this->contactusEmail = 'contactus@attorney.bio';
		$this->thankyouEmail = 'thankyou@attorney.bio';

		//for lawyer
		//$this->siteDomain = 'lawyer.bio';
		//$this->siteName = 'lawyer.BIO';
		//$this->contactusEmail = 'contactus@lawyer.bio';
		//$this->thankyouEmail = 'thankyou@lawyer.bio';
	}

	public function sendEmail($from, $to, $subject, $message)
	{
        $this->load->library('email');
        $this->email->set_newline("\r\n");
        $this->email->from($from);
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        if ($this->email->send()) {
            return  true;
        } else {
			//show_error($this->email->print_debugger());
			return  false;
        }		
	}


	public function recoverpassword()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$email = $request['email'];

		if($email=='')
			return $this->reply(400, 'missied email', null);

		$user = $this->User_model->getRow(['email'=>$email]);
		if($user==null)
			return $this->reply(401, 'There is no account with that email.', null);

		$id= password_hash($this->siteName.$email, PASSWORD_DEFAULT);
		$url = "https://".$this->siteDomain.'/resetpassword?id='.$id;

		$contents = "You requested reset password."."\r\n\r\n".
					"Please click bellow link to reset password.\r\n\r\n".$url;
		$this->sendEmail($this->contactusEmail, $email, "Reset Password", $contents);
		return $this->reply(200, '', null);
	}

	public function resetpassword()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$email = $request['email'];
		$hash = $request['hash'];
		$password = $request['password'];

		if($hash=='')
			return $this->reply(400, 'Something went as wrong.', null);

		$email = $request['email'];
		if($email=='')
			return $this->reply(401, 'Something went as wrong.', null);
	
		$password = $request['password'];
		if($password=='')
			return $this->reply(402, 'Something went as wrong.', null);		

		if(!password_verify($this->siteName.$email, $hash))
			return $this->reply(403, 'Something went as wrong.', null);

		$user = $this->User_model->getRow(['email'=>$email]);
		if($user==null)
			return $this->reply(404, 'Something went as wrong.', null);

		$user = $this->User_model->updateData(['Id'=>$user->Id], ['password'=>password_hash($password, PASSWORD_DEFAULT)]);
		return $this->reply(200, '', null);
	}


	public function contactus()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$email = $request['email'];

		if($email=='')
			return $this->reply(400, 'missied email', null);
		$name = $request['name'];
		if($name=='')
			return $this->reply(400, 'missied name', null);

		$content = $request['content'];
		if($content=='')
			return $this->reply(400, 'missied content', null);

		$isMember = $request['isMember'];
		$member = 'No';
		if($isMember >0) 
			$member = 'Yes';

		$contents = "From: " . $name. "\r\n" . "IsMember: ". $member. "\r\n\r\n".$content;
		$this->sendEmail($email, $this->contactusEmail, "Contact US", $contents);
		return $this->reply(200, '', null);
	}


	private function thankyouRegisterEmial($to, $link)
	{
		// $contents = "You can customize and change the content at any time.  \r\n Your personal BIO page is located at: ".$link.
		// 	" (whatever the URL is that they selected).\r\n\r\n".
		// 	"Feel free to share it around or post it anywhere you like.";
		// $this->sendEmail($this->thankyouEmail, $to, "Thank you for signing up for a BIO Page.",
		//  	$contents);		
	}


	private function createToken($user, $duration)
	{
		unset($user->password);
		unset($user->created_at);
		unset($user->logined_at);
		unset($user->token);

		$user->expire = time() + $duration;
		$jwtClaim = base64_encode(json_encode($user));
		$privKey = "-----BEGIN PRIVATE KEY-----\nMIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAMUUd9+K5gph2JDi\ny/9wQo6XSnYc+3UP2Kh1KJhlkCGulOAP4RdpwGyx8C1HTJ3BxW0Kr45sD3fOO5Hq\nV/BSaeg7QQUcGz/israXH1VQ1uKnMOFOKQpp4MLgqwxiBUHNksX45O86P/1ZXU0n\n+V/ar5n2CGvRIuGO4M05tRkQX0ixAgMBAAECgYBSGfLOV6j5zkVQJothgLzZdkF4\n7x36aH2TwCsHQdhHj9lJdpQJEu8s2Pv7gOZ0GhNLF9aG+JGBEZNTeFLmNQ8VCep6\neuNnjyza/bMuS38e7hmMcsx+kijqQfr7kpWACq1eXROe4W80tfp6S1NXR36He1RE\nj9SCH9pZCkMMaAMROQJBAPjCK2XJCEwss952dwj0DY1KktKjepgHoH4VvZZ/u2xH\nX5fkFtYR6ONpwhQqKy/6nS2aZ03Grt4wNZqQ+sw2ZpsCQQDK0SxdDpbBS6/FTDVw\neTXbNAPEOBRXrQXaYsWoKWc/gbyogmlcyllZUqNiJRvGA3ESqEQfgg+n49+41Qea\nORyjAkAFl0+kZQVTuPl9+YmpYNrhHxj3tQbvXdSEoPZ26H4M6/nBDzZYL2Tdn6Xm\nECCSXn6j5MGHpPyPL+Q0iieo6VwbAkBgU1Q0pjcneuDyXa2Ly6WuhFe9m7zdn0mG\nXR7vLSriWKXXioisD1a8O6lpdaJpOz061Tv1kvoIjQu2Z0cDEljtAkEAzNDRxY2s\nX5quiGDLnllmdotbolqDtmCcb5fJXMAozEfOfzZcbAmnGDTzrbmo6++KwIO1hE7q\n4eGDQV1koNu4Dg==\n-----END PRIVATE KEY-----";
		openssl_sign($jwtClaim,	$jwtSig, $privKey,	"sha256WithRSAEncryption");
		$jwtSign = base64_encode($jwtSig);
		return $jwtClaim.'.'.$jwtSign;
	}

	public function login() {
		$request = json_decode(file_get_contents('php://input'), true);
		$email = $request['email'];
		if($email=='')
			return $this->reply(400, 'missied email', null);

		$password = $request['password'];
		if($password=='')
			return $this->reply(400, 'missied password', null);

		$localtime = $request['localtime'];
		if($localtime=='')
			return $this->reply(400, 'missied localtime', null);
	
		$user = $this->User_model->getRow(['email'=>$email]);
		if($user==null)
			return $this->reply(400, 'invalid email or password', null);
		if(!password_verify($password, $user->password))
			return $this->reply(400, 'wrong password', null);

		$token = $this->createToken($user, $this->tokenDuration);

		//$this->User_model->updateData(['Id'=>$user->Id], ['logined_at'=>date('Y-m-d h:i:s')]);
		$this->User_model->updateData(['Id'=>$user->Id], ['token'=>$token,'logined_at'=>date('Y-m-d h:i:s')]);
		$this->reply(200, 'success', ['token'=>$token, 'expire'=>$localtime + $this->tokenDuration]);
	}

	public function regenToken()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if($userInfo==null) return;

		$localtime = $request['localtime'];
		if($localtime=='')
			return $this->reply(400, 'missied localtime', null);	

		$user = $this->User_model->getRow(['Id'=>$userInfo->userId]);
		if($user==null)
			return $this->reply(401, 'Invalid user info!', null);

		$token = $this->createToken($user, $this->tokenDuration);
		$this->User_model->updateData(['Id'=>$user->Id], ['token'=>$token,'logined_at'=>date('Y-m-d h:i:s')]);		
		$this->reply(200, 'success', ['token'=>$token, 'expire'=>$localtime + $this->tokenDuration]);
	}

	public function register() {
		$request = json_decode(file_get_contents('php://input'), true);		
		if($request['email']=='')
			return $this->reply(400, 'missied email', null);
		if($request['password']=='')
			return $this->reply(400, 'missied password', null);
		if($request['name']=='')
			return $this->reply(400, 'missied name', null);

		$localtime = $request['localtime'];
		if($localtime=='')
			return $this->reply(400, 'missied localtime', null);
		
		$user = $this->User_model->getRow(['email'=>$request['email']]);
		if($user!=null)
			return $this->reply(400, 'email is already using', null);

		$request['password'] = password_hash($request['password'], PASSWORD_DEFAULT);
		$request['created_at'] = date('Y-m-d h:i:s');
		$request['logined_at'] = date('Y-m-d h:i:s');

		unset($request['localtime']);
		$userId = $this->User_model->insertData($request);
		$user = $this->User_model->getRow(['Id'=>$userId]);
		if($user==null)
			return $this->reply(402, 'Register failed', null);

		//send thank you email
		$this->thankyouRegisterEmial($request['email'], "https://".$this->siteDomain."/");

		$token = $this->createToken($user, $this->tokenDuration);
		$this->reply(200, 'success', ['token'=>$token, 'expire'=>$localtime + $this->tokenDuration]);
	}

}