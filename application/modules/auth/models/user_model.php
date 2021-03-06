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
 * An open source development control panel written in PHP
 *
 * @package		BackendPro
 * @author		Adam Price
 * @copyright	Copyright (c) 2008, Adam Price
 * @license		http://www.gnu.org/licenses/lgpl.html
 * @link		http://www.kaydoo.co.uk/projects/backendpro
 * @filesource
 */

// ------------------------------------------------------------------------
/**
 * User_model
 *
 * Provides functionaly to query all tables related to the
 * user.
 *
 * @package   	BackendPro
 * @subpackage  Models
 */
class User_model extends Base_model
{
	public $error       = array();
    public $error_count = 0;
	
	function __construct()
	{
		parent::__construct();

		//$this->lang->load('userlib');
		$this->_prefix = $this->config->item('backendpro_table_prefix');
		$this->_TABLES = array(    'Users' => $this->_prefix . 'users',
                                    'UserProfiles' => $this->_prefix . 'user_profiles');

		log_message('debug','BackendPro : User_model class loaded');
	}

	/**
	 * Validate Login
	 *
	 * Verify that the given login_field and password
	 * are correct
	 *
	 * @access public
	 * @param string $login_field Email/Username
	 * @param string $password Users password
	 * @return array('valid'=>bool,'query'=>Query)
	 */
	function validateLogin($login_field, $password)
	{
	
	  
		if( !$password OR !$login_field)
		{
			// If there is no password
			return array('valid'=>FALSE,'query'=>NULL);
		}

		switch($this->preference->item('login_field'))
		{
			case'email':
				$this->db->where('email',$login_field);
				break;

			case 'username':
				$this->db->where('username',$login_field);
				break;

			default:
			    $this->db->where('(email = '.$this->db->escape($login_field).' OR username = '.$this->db->escape($login_field).')');
			    break;
		}
//echo "test test==".$password;
//die;
		$this->db->where('password',$password);

		$query = $this->fetch('Users','id,active');
		
		$found = ($query->num_rows() == 1);
		return array('valid'=>$found,'query'=>$query);
	}

	/**
	 * Update Login Date
	 *
	 * Updates a users last_visit record to the current time
	 *
	 * @access public
	 * @param integer $user_id Users user_id
	 */
	function updateUserLogin($id)
	{
		$this->update('Users',array('last_visit'=>date ("Y-m-d H:i:s")),array('id'=>$id));
	}

	/**
	 * Valid Email
	 *
	 * Checks the given email is one that belongs to a valid email
	 *
	 * @access public
	 * @param string $email Email to validate
	 * @return boolean
	 */
	function validEmail($email)
	{
		$query = $this->fetch('Users',NULL,NULL,array('email'=>$email));
		return ($query->num_rows() == 0) ? FALSE : TRUE;
	}

	/**
	 * Activate User Account
	 *
	 * When given an activation_key, make that user account active
	 *
	 * @access public
	 * @param string $key Activation Key
	 * @return boolean
	 */
	function activateUser($key)
	{
		$this->update('Users', array('active'=>'1','activation_key'=>NULL), array('activation_key'=>$key));

		return ($this->db->affected_rows() == 1) ? TRUE : FALSE;
	}

	/**
	 * Get Users
	 *
	 * @access public
	 * @param mixed $where Where query string/array
	 * @param array $limit Limit array including offset and limit values
	 * @return object
	 */
	function getUsers($where = NULL, $limit = array('limit' => NULL, 'offset' => ''))
	{
		// Load the khacl config file so we can get the correct table name
		$this->load->config('auth/khaos', true, true);
		$options = $this->config->item('acl', 'khaos');
		$acl_tables = $options['tables'];

		// If Profiles are enabled load get their values also
		$profile_columns = '';
		if($this->preference->item('allow_user_profiles'))
		{
			// Select only the column names of the profile fields
			$profile_fields_array = array_keys($this->config->item('userlib_profile_fields'));

			// Implode and seperate with comma
			$profile_columns = implode(', profiles.',$profile_fields_array);
			$profile_columns = (empty($profile_fields_array)) ? '': ', profiles.'.$profile_columns;
		}

		$this->db->select('users.id, users.username, users.email, users.password, users.active, users.last_visit, users.created, users.modified, groups.name `group`, groups.id group_id'.$profile_columns);
		$this->db->from($this->_TABLES['Users'] . " users");
		$this->db->join($this->_TABLES['UserProfiles'] . " profiles",'users.id=profiles.user_id');
		$this->db->join($acl_tables['aros'] . " groups",'groups.id=users.group');
		if( ! is_null($where))
		{
			$this->db->where($where);
		}
		if( ! is_null($limit['limit']))
		{
			$this->db->limit($limit['limit'],( isset($limit['offset'])?$limit['offset']:''));
		}
		return $this->db->get();
	}

	/**
	 * Delete Users
	 *
	 * Extend the delete users function to make sure we delete all data related
	 * to the user
	 *
	 * @access private
	 * @param mixed $where Delete user where
	 * @return boolean
	 */
	function _delete_Users($where)
	{
		// Get the ID's of the users to delete
		$query = $this->fetch('Users','id',NULL,$where);
		foreach($query->result() as $row)
		{
			$this->db->trans_begin();
			// -- ADD USER REMOVAL QUERIES/METHODS BELOW HERE

			// Delete main user details
			$this->db->delete($this->_TABLES['Users'],array('id'=>$row->id));

			// Delete user profile
			$this->delete('UserProfiles',array('user_id'=>$row->id));

			// -- DON'T CHANGE BELOW HERE
			// Check all the tasks completed
			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback();
				return FALSE;
			} else
			{
				$this->db->trans_commit();
			}
		}
		return TRUE;
	}
	
	public function getInstitutes()
	{
	    $data = array();
		$this->db->select('i_id, insttname');
		$this->db->from('institutes');
		$this->db->order_by('insttname');
		$array_keys_values = $this->db->get();
		foreach($array_keys_values->result() as $row)
		{
		   $data[$row->i_id] = $row->insttname;
		}		  
		  return $data;
	   }
/**
*FUNCTION FOR
*REGISTRING NEW
*USE
*/ 
   public function register()
   {
        $row = $this->input->post('row');        
        // check username 
        /*$is_exist_username = $this->db->get_where(TBL_USERS, 
                array('username' => $row['username']))->num_rows();
				
		$is_exist_fullname = $this->db->get_where(TBL_USERS, 
                array('fullname' => $row['fullname']))->num_rows();
		*/		
		$is_exist_email = $this->db->get_where(TBL_USERS, 
                array('email' => $row['email']))->num_rows();
		
		/*$is_exist_phone = $this->db->get_where(TBL_USERS, 
                array('phone' => $row['phone']))->num_rows();
		$is_exist_institute = $this->db->get_where(TBL_USERS, 
                array('institute' => $row['institute']))->num_rows();
		*/	
		
		/// check duplication///
		/*if(($is_exist_fullname > 0) && ($is_exist_email > 0) && ($is_exist_phone > 0) && ($is_exist_institute > 0)){
		    $this->error['duplicate'] = 'You are already registed!';
		}*/
		
        if ($is_exist_email > 0) {
            $this->error['duplicate'] = 'You are already registed!';
        }
        
		/*if (strlen($row['username']) < 5) {
            $this->error['username'] = 'Username minimum 5 character';
        }*/
        
        // check password
        if ($row['password'] != $this->input->post('password2')) {
            $this->error['password'] = 'Password not match';
        } else if (strlen($row['password']) < 5) {
            $this->error['password'] = 'Password minimum 5 character';
        }
		
		if (strlen($row['fullname']) == NULL) {
            $this->error['fullname'] = 'Please enter your fullname';
        }
		
		if (strlen($row['email']) == NULL) {
            $this->error['email'] = 'Please enter your email';
        }
		
		if (strlen($row['phone']) == NULL) {
            $this->error['phone'] = 'Please enter your mobile no.';
        }
		
		/*if (strlen($row['institute']) == NULL) {
            $this->error['institute'] = 'Please choose your institute.';
        }*/
        
        if (count($this->error) == 0) {
            //$key = $this->config->item('encryption_key');
            //$row['password'] = $this->encrypt->encode($row['password'], $key);
			$row['password'] = $this->userlib->encode_password($row['password']);			
			$row['active']=1;
			$row['group']=3;
			$row['username'] = $row['fullname'];
			
            $this->db->insert(TBL_USERS, $row);
			$userid = $this->db->insert_id();
			
			$user_prof['user_id'] =$userid; 
			$this->db->insert("be_user_profiles", $user_prof);
			
			$sess_array = array(
					   'id' => $userid,
					   'fullname' => $row['fullname'],
					   'email' => $row['email'],
					   'group'=>3,
					   'group_id'=>3,
					   'active'=>1,
					   'user_registration'=>"success");
         $this->session->set_userdata($sess_array);
			$key = $this->config->item('encryption_key');
			$user = $this->encryption->encode($userid);
			//$user = urlencode($this->encrypt->encode($userid, $key, TRUE));
			// mail///
			$emailby = 'training@mirus.in';
			$nameby = 'Mirus Solutions';
			$strTo = 'madhu@mirus.in';
			$strSubject = 'New member registered';
			$strMessage = "
			Hello Ma'am,
			I,  $row[fullname], has been registered on your training module.
			My personal information is given below:
			Name:$row[fullname]
			Email id: $row[email]
			Phone No: $row[phone]
			
			Thanks";
			
				 //*** Uniqid Session ***//
			$strSid = md5(uniqid(time()));
	
			$strHeader = "";
			$strHeader .= "From: " . $nameby . "<" . strip_tags($emailby) . ">\r\n";
			$strHeader .= "Reply-To: " . strip_tags($emailby) . "\r\n";
	
			$strHeader .= "MIME-Version: 1.0\n";
			$strHeader .= "Content-Type: multipart/mixed; boundary=\"" . $strSid . "\"\n\n";
			$strHeader .= "This is a multi-part message in MIME format.\n";
	
			$strHeader .= "--" . $strSid . "\n";
			$strHeader .= 'Cc: sonali@mirus.in\r\n';
			 
			$strHeader .= "Content-type: text/html; charset=utf-8\n";
			$strHeader .= "Content-Transfer-Encoding: 7bit\n\n";
	
			$strHeader .= $strMessage . "\n\n";
			
			$flgSend = @mail($strTo, $strSubject, null, $strHeader);
		////////////////////////////////////////////
        } else {
			
            $this->error_count = count($this->error);
        }
    }
/**
*CRERTIFICA NO
*AUTEH
*/
public function certificateno_auth($cerificatno=""){
$cerificatno = trim($cerificatno);
$sql = "SELECT * FROM certificate_no WHERE certificate_no = '".$cerificatno."'";
$query = $this->db->query($sql);
$numrows = $query->num_rows();

//$query = mysql_query($sql);
//$numrows = mysql_num_rows($query);
if($numrows>0){	
  return 'cer_exist';
}
else{
  return 'cer_notexist';
}

}

/**
*FUNCTION FOR CHECK
*USER REGISTER WITH
*CERTIFICAT NO
*/
public function check_user_withcertificatno($cerno=""){
   $sql = "SELECT * FROM be_users WHERE certificate_no = '".$cerno."'";
   $query = $this->db->query($sql);
   $numrows = $query->num_rows();
   if($numrows>0){
	  return 'user_exist';
   }
   else{
	  return 'user_notexist';
   }
}

/**
* FUNCTION FOR 
* CHECK USER EMAIL ID
*/
  function check_user_emailid($emailid = ""){
    $sql = "SELECT id,fullname,email FROM be_users WHERE email='".$emailid."'";
	$query = $this->db->query($sql);
    $numrows = $query->num_rows();
    if($numrows>0){
	 return $query->result_array();
	  //return 'email_exist';
    }
    else{
	  return 'email_notexist';
     }
  }
/**
*FUNCTION FOR 
*GET USER INFO
*ON ID
*/  
  public function get_userinfo($userid=''){
  
    $sql = "SELECT id,fullname,email FROM be_users WHERE id='".$userid."'";
	$query = $this->db->query($sql);
    $numrows = $query->num_rows();
    if($numrows>0){
	 return $query->result_array();	 
    }
  }

/**
*FUNCTION FOR 
*RESET USER PASSWORD
*/
public function resetpassword($userid="",$password=""){
 $newpasswod = $this->userlib->encode_password($password);
 $sql = "update be_users set password='".$newpasswod."'
         WHERE id='".$userid."'";
 $this->db->query($sql);
 return true;
}  
  
}


/* End of file: user_model.php */
/* Location: ./modules/auth/controllers/admin/user_model.php */