# AccessLogs plugin for CakePHP
maintainer: @gorogoroyasu

## Installation

```
composer require fusic/AccessLogs
```

# settings

in controller which you want to save logs,
```
$this->loadComponent('AccessLogs.AccessLogs');
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
        $table->addColumn('user_id',         'integer',      ['null' => true])
              ->addColumn('controller',      'text',         ['null' => false])
              ->addColumn('action',          'text',         ['null' => false])
              ->addColumn('passes',          'text',         ['null' => true])
              ->addColumn('client_ip',       'text',         ['null' => true])
              ->addColumn('created',         'timestamp',    ['null' => false])
              ->create();
    }
}
```
