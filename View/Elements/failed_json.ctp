<?php
	echo json_encode(array('success' => false, 'errorMessage' => $errorMessage, 'validationErrors' => $this->validationErrors));
?>