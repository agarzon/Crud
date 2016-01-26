# CRUD 

Crud Plugin for CakePHP 2.x designed to be used with ExtJS

## Installing

Add into bootstrap.php
```CakePlugin::load('Crud', array('routes' => true));```

Add into your AppController.php
(before *class AppController*)
```App::import('Controller', 'Crud.App');```

Then add ```use CrudAppController;``` inside *class AppController*
