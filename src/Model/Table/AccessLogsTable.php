<?php

namespace AccessLogs\Model\Table;

use Cake\ORM\Table;
use Cake\I18n\FrozenTime;
use Cake\Validation\Validator;

/**
 * TODO: support change columnName option.
 */
class AccessLogsTable extends Table
{
    private $savedEntity;
    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     *
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('user_id')
            ->allowEmpty('user_id')
            ->requirePresence('user_id', 'create');

        $validator
            ->requirePresence('controller', 'create')
            ->notEmpty('controller');

        $validator
            ->requirePresence('action', 'create')
            ->notEmpty('action');

        $validator
            ->allowEmpty('passes');

        $validator
            ->allowEmpty('client_ip');

        $validator
            ->dateTime('created');

        return $validator;
    }

    public function saveLog($array)
    {
        $array['created'] = new FrozenTime();
        $data = $this->newEntity($array);

        if ($this->save($data)) {
            $this->savedEntity = $data;

            return true;
        } else {
            throw new \Cake\Network\Exception\InternalErrorException('couldnt save the log.', 500);
        }
    }

    public function updateLog($array)
    {
        $entity = $this->savedEntity;
        $entity = $this->patchEntity($entity, $array);
        if ($this->save($entity)) {
            $this->savedEntity = $entity;

            return true;
        } else {
            throw new \Cake\Network\Exception\InternalErrorException('couldnt save the log.', 500);
        }
    }

    public function getColumnsInfo()
    {
        return $this->schema()->columns();
    }

    public function checkColumn($saveArray, $columns)
    {
        foreach ($saveArray as $key => $value) {
            if (!in_array($key, $columns)) {
                return false;
            }
        }

        return true;
    }
}
