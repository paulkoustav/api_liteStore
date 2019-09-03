<?php 
	
class fileupload {
	
	public function __construct() {}
	
	public function action() {
				
		$files = array();
		if(isset($_FILES)) {
			$countfiles = count($_FILES['fileUpload']);
			$imagedir = ($_POST['imagedir'] ? 'upload/'.$_POST['imagedir'].'/' : 'upload/');
			
			if($countfiles) {
				 for($i=0;$i<$countfiles;$i++){
					$filename = (isset($_FILES['fileUpload']['name'][$i]) ? time().'_'.$_FILES['fileUpload']['name'][$i]:'');
					$filesize = (isset($_FILES['fileUpload']['size'][$i]) ? $_FILES['fileUpload']['size'][$i]:'');
					$tmp_name = (isset($_FILES['fileUpload']['tmp_name'][$i]) ? $_FILES['fileUpload']['tmp_name'][$i]:'');
					if($filename && $filesize) {
						move_uploaded_file($tmp_name, $imagedir.$filename);
						$files[] = $filename;	
					}
				 }
				 $return['data']['files'] = $files;
				 $return['data']['_POST'] = $_POST;
				 
			} else {
				$return['status'] = 4000;
				$return['msg'] = 'FILES not found';	
			}
		}
								
		return $return;
	}
	
}
?>