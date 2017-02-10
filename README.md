# AccessLogs plugin for CakePHP
maintainer: @gorogoroyasu

## Installation

```
composer require fusic/accesslogs
```

# settings

in controller which you want to save logs,
```
$this->loadComponent('AccessLogs.AccessLogs');

//in case you dont want to save some specific data,
// you have to modify how to load the component like shown below.
// (like, password, credit cart, etc...)

$this->loadComponent('AccessLogs.AccessLogs', ['blacklist' => ['password']]);

```

next, you have to exec the migration.
please copy the code below, and exec it!
```
<?php

use Migrations\AbstractMigration;

class AccessLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     */
    public function change()
    {
        $table = $this->table('access_logs');
        $table->addColumn('user_id',         'integer',        ['null' => true])
              ->addColumn('controller',      'string',         ['null' => true, 'limit' => 255])
              ->addColumn('action',          'string',         ['null' => true, 'limit' => 255])
              ->addColumn('passes',          'string',         ['null' => true, 'limit' => 255])
              ->addColumn('client_ip',       'string',         ['null' => true, 'limit' => 255])
              ->addColumn('url',             'string',         ['null' => true])
              ->addColumn('code',            'string',         ['null' => true, 'limit' => 255])
              ->addColumn('query',           'text',           ['null' => true])
              ->addColumn('data',            'text',           ['null' => true])
              ->addColumn('created',         'timestamp',      ['null' => false])
              ->addIndex('user_id')
              ->addIndex('controller')
              ->addIndex('action')
              ->addIndex('passes')
              ->addIndex('client_ip')
              ->addIndex('code')
              ->create();
    }
}
```
