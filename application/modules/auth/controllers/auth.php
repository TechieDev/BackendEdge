<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * BackendEdge
 *
 * An updated version of BackedPro, an open source development control panel written in PHP
 *
 * @package		BackendEdge
 * @author		Dev Kumar
 * @copyright	Copyright (c) 2013, Dev Kumar
 * @license		http://www.gnu.org/licenses/lgpl.html
 * @link		https://github.com/TechieDev/BackendEdge
 * @email       techiedev1987@gmail.com
 * @filesource
 */
/**
 * BackendPro
 *
 * A website backend system for developers for PHP 4.3.2 or newer
 *
 * @package         BackendPro
 * @author          Adam Price
 * @copyright       Copyright (c) 2008
 * @license         http://www.gnu.org/licenses/lgpl.html
 * @link            http://www.kaydoo.co.uk/projects/backendpro
 * @filesource
 */

// ---------------------------------------------------------------------------

/**
 * auth.php
 *
 * Authentication Controller
 *
 * @package			BackendPro
 * @subpackage		Controllers
 */
class auth extends Public_controller
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// Load the Auth_form_processing class
		$this->load->library('auth/auth_form_processing');
		log_message('debug','BackendPro : Auth class loaded');
                //$this->output->enable_profiler(TRUE);
				
				$this->load->model('user_model');
	}

	function index()
	{
		$this->login();
	}

	function login()
	{
		/*$ses_data = $this->session->userdata('user_registration');
		if($ses_data == "success"){
			redirect('question/admin/index', 'refresh');
		}else{
			$this->auth_form_processing->login_form($this->_container);
		}*/
		$this->auth_form_processing->login_form($this->_container);
		
		
	}

        function login_submit()
        {
            $this->auth_form_processing->login();
        }

	function logout()
	{
		$this->auth_form_processing->logout();
	}

	function forgotten_password()
	{
		$this->auth_form_processing->forgotten_password_form($this->_container);
	}

	function register()
	{
		$this->auth_form_processing->register_form($this->_container);
	}

	function activate()
	{
		$this->auth_form_processing->activate();
	}
	
	/**
	* FUNCTION FOR CREATE NEW
	* USER
	*/	
	public function create_new_user(){
	
		$frmdata = $this->input->post('row');		
		if ($this->input->post('btn-reg') == "Register") 
        {
		
			//echo  '<pre>'; 
			//print_r($frmdata);
			//die;
		  $cerno = isset($frmdata['certificate_no'])?$frmdata['certificate_no']:'';
	
           $ser_statu =  $this->user_model->certificateno_auth($cerno);
		
		  if($ser_statu == "cer_exist"){
		      $user_status =  $this->user_model->check_user_withcertificatno($cerno);
			if($user_status == "user_notexist"){
			
				$this->user_model->register();
				 if ($this->user_model->error_count != 0) {
					$data['error']    = $this->user_model->error;
				} else {
					$this->session->set_userdata('tmp_success', 1);              
					$this->session->set_userdata('user_registration', "success");
					$data['user_registration_status'] = "success";
				}
			}
			else{
				$data['fullname'] = $frmdata['fullname'];
				$data['alias_name']= $frmdata['alias_name'];
				$data['email'] = $frmdata['email'];
				$data['phone'] =  $frmdata['phone'];
				$data['institute'] = $frmdata['institute'];
				$data['username'] = isset($frmdata['username'])?$frmdata['username']:'';
				$data['certificate_no'] = $frmdata['certificate_no'];
				$data['ser_statu'] = "user_exist";
			}
				
		  }
		  else{
		     
		    $data['fullname'] = $frmdata['fullname'];
            $data['alias_name']= $frmdata['alias_name'];
            $data['email'] = $frmdata['email'];
            $data['phone'] =  $frmdata['phone'];
            $data['institute'] = $frmdata['institute'];
			$data['username'] = isset($frmdata['username'])?$frmdata['username']:'';
			$data['certificate_no'] = $frmdata['certificate_no'];
		    $data['ser_statu'] = "not_exist";
		  }
			 
		}
	
	    $data['institute'] = $this->user_model->getInstitutes();
		$data['header']="Create new user";
		//$data['header'] = $this->lang->line('backendpro_members');
		$data['page'] = $this->config->item('backendpro_template_public') . "create_new_user";
		$data['module'] = 'auth';
		$this->load->view($this->_container,$data);
	}
/**
*FUNCTION FOR 
*AUTHENTICATE
*CERTIFICATE NO
*/
public function authenticate_certificate_no(){
  $cerno = $_REQUEST['cerno'];
	
  echo  $this->user_model->certificateno_auth($cerno);
  die;
}

/**
*function for forgotpassword
*/
public function forgotpassword(){

if($this->input->post()){
 $frmdata = $this->input->post('row');
 
 $email_status = $this->user_model->check_user_emailid($frmdata['email']);
 if($email_status == "email_notexist"){
   $data['emailid'] = $frmdata['email'];
   $data['emailmsg'] = "Email id does not exist.";
 }
 else{
     //echo '<pre>';
	 //print_r($email_status);
	 //die;
	    $userid = isset($email_status[0]['id'])?$email_status[0]['id']:'';
		$fullname = isset($email_status[0]['fullname'])?$email_status[0]['fullname']:'';
		$emailid = isset($email_status[0]['email'])?$email_status[0]['email']:'';
		$message = "<table>
		           <tr><td>Dear $fullname,<br><br>
					you requested that your password be reset. Please visit the link 
					below of copy and past it into your browser to create a new password.
					<br><br>
					<br><br>
					</td></tr>
					<tr>
					  <td>".base_url()."index.php/auth/resetpassword/".$userid."</td>
					</tr>

				<tr>
				<td><br><br>Thanks,<br><br>
				    Triedge Solution
				</td></tr>
		</table>";
		//echo $message;
		//die;
		$to = $emailid;
		$subject = 'Forgot Password';
		$header = "From: contact@triedge.in\r\n"; 
		$header.= "MIME-Version: 1.0\r\n"; 
		$header.= "Content-type: text/html; charset=iso-8859-1;\r\n"; 
		$header.= "X-Priority: 1\r\n"; 
		mail($to, $subject, $message, $header);		
	    $data['email_send_status'] = "1";	
   }
}
$data['header']="Forgot Password";
$data['page'] = $this->config->item('backendpro_template_public') . "forgot_password";
$data['module'] = 'auth';
$this->load->view($this->_container,$data);
}

/**
*FUNCTION FOR
*RESET USER 
*PASSWORD
*/
public function resetpassword(){
 
    $userid = $this->uri->segment(3);
	if($this->input->post()){
	    $frmdata = $this->input->post();
		$userid = $frmdata['userid'];		
		$userpass = $frmdata['password'];
		$this->user_model->resetpassword($userid,$userpass);
		//echo '<pre>';
		//print_r($frmdata);
		//die;
		redirect('http://connect.triedge.in/','refresh');
	}
	
	
    $data['userinfo'] = $this->user_model->get_userinfo($userid); 
	$data['header']="Reset Password";
	$data['page'] = $this->config->item('backendpro_template_public') . "resetpassword";
	$data['module'] = 'auth';
	$this->load->view($this->_container,$data);
 
}
	
}
/* End of file auth.php */
/* Location: ./modules/auth/controllers/auth.php */
