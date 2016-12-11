<?php

namespace AccessLogs\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

// IDEA: beforeRender では、削除が取れない。
class AccessLogsComponent extends Component
{
    private $controller;
    private $table;

    protected $tableName = 'AccessLogs.AccessLogs';

    // column name settings
    protected $client_ip = 'client_ip';
    protected $action_name = 'action';
    protected $controller_name = 'controller';
    protected $pass_name = 'passes';
    // user ID read from auth component.
    protected $user_id = 'user_id';

    public function initialize(array $config)
    {
        parent::initialize($config);
        // DB接続
        try {
            $this->table = TableRegistry::get($this->tableName);
        } catch (Exception $e) {
            throw new \Cake\Error\FatalErrorException('couldn\'t find table in current database.');
        }
    }

    /**
     * beforeFilter this function is the main method in this plugin.
     * NOTE: if this method were beforeRender, cannot get Delete Action, because that action will not render anything.
     */
    public function beforeFilter(Event $event)
    {

        // controller object を取得
        $this->controller = $this->_registry->getController();

        // 保存する配列を作るところ。
        $saveArray = $this->createSaveArray();
        $columnInfo = $this->columnsInfo();
        // $this->saveLog();
        $isSavable = $this->isSavable($saveArray, $columnInfo);

        // save できるかをチェック
        if (!$isSavable) {
            return false;
        }
        // エンティティを作成
        $isSaved = $this->logging($saveArray);

        if (!$isSaved) {
            return false;
        }

        return true;
    }

    protected function logging($saveArray)
    {
        return $this->table->saveLog($saveArray);
    }

    protected function columnsInfo()
    {
        return $this->table->getColumnsInfo();
    }

    protected function isSavable($saveArray, $columns)
    {
        // セーブできるかどうかを調べる。
        return $this->table->checkColumn($saveArray, $columns);
    }

    /**
     * [createSaveArray 保存用の配列を生成.
     */
    protected function createSaveArray()
    {
        $returnArray = [];
        $returnArray[$this->user_id] = $this->getAuthInfo();
        $returnArray[$this->client_ip] = $this->getGlobalIp();
        $returnArray[$this->action_name] = $this->getRequestInfo('action');
        $returnArray[$this->controller_name] = $this->getRequestInfo('controller');
        $returnArray[$this->pass_name] = $this->getRequestInfo('pass');

        return $returnArray;
    }

    protected function getAuthInfo()
    {
        $user = $this->controller->Auth->user();
        if (!isset($user['id'])) {
            return;
        }

        return $user['id'];
    }

    protected function getGlobalIp()
    {
        return $this->request->clientIp(false);
    }

    /**
     * getActionsInfo.
     *
     * @return [type] [description]
     */
    protected function getRequestInfo($param)
    {
        $requestParams = $this->controller->request->params;
        if (!isset($requestParams[$param])) {
            return false;
        }
        if (is_array($requestParams)) {
            return json_encode($requestParams[$param]);
        }

        return $requestParams[$param];
    }
}
