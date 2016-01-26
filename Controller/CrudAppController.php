<?php

trait CrudAppController {

	/**
	 * List of fields to include in the find when editing.
	 * If $this->_editFields is null $this->paginate will be used.
	 *
	 * @var array $_editFields
	 */
	protected $_editFields = array();

	/**
	 * List of model fields to be saved.
	 *
	 * @var array $_fieldList
	 */
	protected $_fieldList = array();

	/**
	 * Default actions
	 *
	 * @var array $_defaultActions
	 */
	protected $_defaultActions = array(
		'admin_index',
		'admin_list',
		'admin_add',
		'admin_duplicate',
		'admin_edit',
		'admin_delete'
	);

	/**
	 * admin_index default action
	 *
	 * @return string json response
	 */
	public function admin_index() {
		// Hack to get only ENG translations for grids
		$this->{$this->modelClass}->locale = 'eng';
		$this->_paginateJson($this->paginate());
	}

	/**
	 * admin_list default action
	 *
	 * @return string json response
	 */
	public function admin_list() {
		// Hack to query only ENG translation
		$this->{$this->modelClass}->locale = 'eng';

		$list = $this->{$this->modelClass}->find('all', array(
			'order' => array('id DESC'),
			'recursive' => -1
		));

		$list = Set::extract("{n}.{$this->modelClass}", $list);
		$this->_setJson($list);
	}

	/**
	 * admin_add default action
	 *
	 * @return string json response
	 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->{$this->modelClass}->create();
			if ($this->{$this->modelClass}->save($this->request->data, true, $this->__getFieldList())) {
				$this->_setSuccessJson($this->{$this->modelClass}->id);
			} else {
				$this->_setFailedJson("The {$this->__getHumanizedName()} couldn't be created");
			}
		} else {
			$this->_setFailedJson();
		}
	}

	/**
	 * admin_duplicate default action
	 *
	 * @return string json response
	 */
	public function admin_duplicate($id = null) {
		$this->admin_edit($id, true);
	}

	/**
	 * admin_edit default action
	 *
	 * @param integer $id identifier
	 * @param boolean $duplicate duplicate entry if is true
	 * @return string json response
	 */
	public function admin_edit($id = null, $duplicate = false) {
		if ($this->request->is('post')) {
			if ($duplicate) {
				$this->{$this->modelClass}->id = null;
				$this->{$this->modelClass}->create();
			} else {
				$this->{$this->modelClass}->id = $id;
			}

			if ($this->{$this->modelClass}->save($this->request->data, true, $this->__getFieldList())) {
				$this->_setSuccessJson($this->{$this->modelClass}->id);
			} else {
				$this->_setFailedJson("The {$this->__getHumanizedName()} couldn't be saved");
			}
		} elseif ($id != null) {
			if (empty($this->_editFields)) {
				$this->_editFields = $this->paginate;
			} elseif (!isset($this->_editFields['fields'])) {
				$this->_editFields = array('fields' => $this->_editFields);
			}
			$data = $this->{$this->modelClass}->find('first', array_merge(
				$this->_editFields,
				array('conditions' => array("{$this->modelClass}.id" => $id))
				));
			if (!empty($data)) {
				$this->_setJson($data);
			} else {
				$this->_setFailedJson('The data couldn\'t be loaded');
			}
		} else {
			$this->_setFailedJson();
		}
	}

	/**
	 * admin_delete default action
	 *
	 * @param integer $ids identifiers
	 * @return string json response
	 */
	public function admin_delete($ids) {
		if (!empty($ids)) {
			$ids = json_decode($ids);
			$fail = false;

			if (is_array($ids)) {
				// Deletion loop
				foreach ($ids as $id) {
					if (!$this->{$this->modelClass}->delete($id)) {
						$fail = true;
					}
				}
			} else {
				$id = $ids;
				// Deletion simple
				if (!$this->{$this->modelClass}->delete($id)) {
					$fail = true;
				}
			}

			if (!$fail) {
				$this->_setSuccessJson($id);
			} else {
				$this->_setFailedJson();
			}
		} else {
			$this->_setFailedJson();
		}
	}

	/**
	 * Add ENGLISH - FRENCH
	 *
	 * @return json
	 */
	protected function _addMultiLang() {
		if ($this->request->is('post')) {

			$this->{$this->modelClass}->create();
			$dataSource = $this->{$this->modelClass}->getDataSource();
			$dataSource->begin($this->{$this->modelClass});

			$commit = false;
			$this->{$this->modelClass}->Behaviors->enable('Translate');
			$this->{$this->modelClass}->locale = 'fra';
			$commit = $this->{$this->modelClass}->save($this->request->data[$this->modelClass . 'Fra']);
			$this->{$this->modelClass}->locale = 'eng';
			$commit = $this->{$this->modelClass}->save($this->request->data) && $commit;

			if ($commit) {
				$dataSource->commit($this->{$this->modelClass});
				$this->_setSuccessJson($this->{$this->modelClass}->id);
			} else {
				$dataSource->rollback($this->{$this->modelClass});
				$this->_setFailedJson("The {$this->__getHumanizedName()} couldn't be created");
			}
		} else {
			$this->_setFailedJson();
		}
	}

	/**
	 * Edit or duplicate ENGLISH - FRENCH
	 *
	 * @param integer $id identifier
	 * @param boolean $duplicate duplicate entry if is true
	 * @return json
	 */
	protected function _editMultiLang($id = null, $duplicate = false) {
		$this->{$this->modelClass}->Behaviors->enable('Translate');
		$dataSource = $this->{$this->modelClass}->getDataSource();
		$dataSource->begin($this->{$this->modelClass});
		$commit = false;

		if ($this->request->is('post')) {

			// Save as new record
			if ($duplicate) {
				$this->{$this->modelClass}->id = null;
				$this->{$this->modelClass}->create();
			} else {
				$this->{$this->modelClass}->id = $id;
			}

			$this->{$this->modelClass}->locale = 'fra';
			$commit = $this->{$this->modelClass}->save($this->request->data[$this->modelClass . 'Fra']);
			$this->{$this->modelClass}->locale = 'eng';
			$commit = $this->{$this->modelClass}->save($this->request->data) && $commit;

			if ($commit) {
				$dataSource->commit($this->{$this->modelClass});
				$this->_setSuccessJson($this->{$this->modelClass}->id);
			} else {
				$dataSource->rollback($this->{$this->modelClass});
				$this->_setFailedJson("The {$this->__getHumanizedName()} couldn't be saved");
			}
		} else {
			$this->{$this->modelClass}->locale = 'eng';
			$this->request->data = $this->{$this->modelClass}->read(null, $id);
			$this->{$this->modelClass}->locale = 'fra';
			$this->request->data[$this->modelClass . 'Fra'] = $this->{$this->modelClass}->read(null, $id);
			$this->_setJson($this->request->data);
		}
	}

	/* RENDERS */

	/**
	 * Watch for inactivity sessions
	 *
	 * Return the following vars:
	 * boolean 'logged' if user has session authenticated
	 * integer 'remainingTime' showing the time remaining before session expire, in minutes.
	 *
	 * @return json
	 */
	public function watcher() {
		$user = $this->Session->read('Auth.User');
		$data['logged'] = !empty($user);
		$data['remainingTime'] = round(($this->Session->read('Config.time') - time()) / 60);

		$this->set(compact('data'));
		$this->plugin = 'Crud';
		$this->render('/Elements/json', 'ajax');
	}

	/**
	 * Renders the data array as json.
	 *
	 * @param array $data data to convert to json
	 * @return void
	 */
	protected function _setJson($data = array()) {
		$this->set(compact('data'));
		$this->plugin = 'Crud';
		$this->render('/Elements/json', 'ajax');
	}

	/**
	 * Renders the paginate data array as json
	 *
	 * @param array $data The paginated result
	 * @param string $model The root of the paginated result, used to parse the json with ExtJS
	 * @return void
	 */
	protected function _paginateJson($data = array(), $model = null) {
		if ($model == null) {
			$model = $this->modelClass;
		}

		$this->set(compact('model', 'data'));
		$this->plugin = 'Crud';
		$this->render('/Elements/paginated_json', 'ajax');
	}

	/**
	 * Renders a success json response.
	 *
	 * You must provide an id if you want to execute the js callback
	 * of the onSubmit function of Beezid.FormWindow.js.
	 *
	 * @param string $id identifier
	 * @param string $msg message
	 * @return void
	 */
	protected function _setSuccessJson($id = null, $msg = '') {
		$success = array('success' => true);
		if ($id || $msg) {
			$success = array_merge($success, compact('id', 'msg'));
		}
		$this->_setJson($success);
	}

	/**
	 * Renders a failed message as json
	 *
	 * @param string $errorMessage message to show as error
	 * @return void
	 */
	protected function _setFailedJson($errorMessage = '') {
		$this->set(compact('errorMessage'));
		$this->plugin = 'Crud';
		$this->render('/Elements/failed_json', 'ajax');
	}

	/* INTERNAL FUNCTIONS */

	/**
	 * This method will automatically add conditions to the pagination based
	 * on the grid filters.
	 *
	 * @param mixed $object extended object
	 * @param array $scope extended scope
	 * @param array $whitelist extended whiterlist
	 * @return class parent::paginate modified
	 */
	public function paginate($object = null, $scope = array(), $whitelist = array()) {
		// Set sort if is requested by query
		if (isset($this->request->query['sort'])) {
			$sort = json_decode($this->request->query['sort']);
			if (isset($this->paginate['order'])) {
				$this->paginate['order'] = array_merge(
					$this->paginate['order'],
					array($sort[0]->property => $sort[0]->direction)
					);
			} else {
				$this->paginate['order'] = array($sort[0]->property => $sort[0]->direction);
			}
		}

		// Set limit if is requested by query
		if (isset($this->request->query['limit'])) {
			$this->paginate['limit'] = $this->request->query['limit'];
		}

		// Set page if is requested by query
		if (!empty($this->request->query['page'])) {
			$this->paginate['page'] = $this->request->query['page'];

		}
		// Set filter if is requested by query
		if (isset($this->request->query['filter'])) {
			$this->__setPaginationScope($scope, $this->request->query['filter']);
		}

		return parent::paginate($object, $scope, $whitelist);
	}

	/**
	 * Creates the pagination conditions based on the filter received
	 *
	 * @param array &$scope Reference of the pagination scope
	 * @param array $filters The filters posted
	 * @return void
	 */
	private function __setPaginationScope(&$scope = array(), $filters = array()) {
		foreach ($filters as $filter) {
			// Verifies if the field strats with '_' (Multiple filter for the same field)
			$modelAndField = explode('.', $filter['field']);
			if (substr($modelAndField[1], 0, 1) == '_') {
				$filter['field'] = $modelAndField[0] . '.' . substr($modelAndField[1], 1);
			}

			switch ($filter['data']['type']) {
				case 'string':
					$value = $filter['data']['value'];
					/* Multiple string management (i.e. 'str1, str2' or 'str3 ; str4' or 'str5% ; =str6') */
					if (strpos($filter['data']['value'], ',') || strpos($filter['data']['value'], ';')) {
						$names = $filter['data']['value'];
						$names = preg_replace('/;/', ',', $names);
						$names = explode(',', $names);

						foreach ($names as $name) {
							$name = trim($name);
							if (empty($name)) {
								continue;
							}

							if (substr($name, 0, 1) == '=') {
								$scope['OR'][] = array($filter['field'] => substr($name, 1));
								continue;
							}
							if (strrpos($name, '%') === false) {
								$name = '%' . $name . '%';
							}
							$scope['OR'][] = array($filter['field'] . ' LIKE' => $name);
						}
						break;
					}

					/* =str1 (exact search) */
					if (substr($value, 0, 1) == '=') {
						$scope = array_merge($scope, array($filter['field'] => substr($value, 1)));
						break;
					}

					// NOT LIKE search
					if (substr($value, 0, 2) == '!=') {
						$scope = array_merge($scope, array($filter['field'] . ' !=' => substr($value, 2)));
						break;
					}

					// IS NULL search
					if (strtolower($value) == 'is null') {
						$scope = array_merge($scope, array($filter['field'] => null));
						break;
					}

					/* str1% or %str2 or %str3% (search string at the beginning, end or within the field) */
					if (strrpos($filter['data']['value'], '%') === false) {
						$value = '%' . $filter['data']['value'] . '%';
					}

					$scope = array_merge($scope, array($filter['field'] . ' LIKE' => $value));
				break;
				case 'list':
					// Values can be delimited by "," or ";"
					$filter['data']['value'] = str_replace(';', ',', $filter['data']['value']);
					$scope = array_merge($scope, array($filter['field'] => explode(',', $filter['data']['value'])));
					break;
					case 'boolean':
					$value = ($filter['data']['value'] == 'true') ? 1 : 0;
					$scope = array_merge($scope, array($filter['field'] => $value));
					break;
					case 'numeric' :
					switch ($filter['data']['comparison']) {
						case 'eq':
							$scope = array_merge($scope, array($filter['field'] => $filter['data']['value']));
						break;
						case 'lt':
							$scope = array_merge($scope, array($filter['field'] . ' <' => $filter['data']['value']));
						break;
						case 'gt':
							$scope = array_merge($scope, array($filter['field'] . ' >' => $filter['data']['value']));
						break;
					}
				break;
				case 'date' :
					// MySql DATE function is not used because it doesn't use indexed datetime fields
					$date = date('Y-m-d', strtotime($filter['data']['value']));
					$dateMin = $date . ' 00:00:00';
					$dateMax = $date . ' 23:59:59';

					switch ($filter['data']['comparison']) {
						case 'eq':
							$scope = array_merge($scope, array(
								$filter['field'] . ' >=' => $dateMin,
								$filter['field'] . ' <=' => $dateMax
							));
						break;
						case 'gt':
							$scope = array_merge($scope, array($filter['field'] . ' >' => $dateMax));
						break;
						case 'lt':
							$scope = array_merge($scope, array($filter['field'] . ' <' => $dateMin));
						break;
					}
				break;
			}
		}
	}

	/**
	 * Returns a list of model fields to be saved.
	 * If not specified the fields specified in AppController::_editFields.
	 *
	 * @return array Field list to be saved.
	 */
	private function __getFieldList() {
		if (!empty($this->_fieldList)) {
			return $this->_fieldList;
		} elseif (!empty($this->_editFields) && isset($this->_editFields['fields'])) {
			return $this->_editFields['fields'];
		} elseif (!empty($this->paginate) && isset($this->paginate['fields'])) {
			return $this->paginate['fields'];
		}

		return array();
	}

/**
 * Humanize models name
 *
 * @return string humanized model name
 */
	private function __getHumanizedName() {
		return Inflector::humanize(Inflector::singularize($this->{$this->modelClass}->table));
	}

}
