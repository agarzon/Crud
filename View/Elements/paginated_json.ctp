<?php
	echo json_encode(array(
		'total' => $this->request->params['paging'][$model]['count'],
		$model => $data
	));
?>