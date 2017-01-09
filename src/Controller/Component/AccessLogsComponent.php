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

    protected $savedLog;

    // column name settings
    protected $client_ip = 'client_ip';
    protected $action_name = 'action';
    protected $controller_name = 'controller';
    protected $pass_name = 'passes';
    protected $query = 'query';
    protected $data = 'data';
    // user ID read from auth component.
    protected $user_id = 'user_id';

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->_defaultConfig += $this->config();
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

        $isSaved = $this->logging($saveArray);

        if (!$isSaved) {
            return false;
        }

        return true;
    }

    /**
     * shutdown in case of the action is Login, beforeFilter cannot get the user_id.
     * so, I use the shutdown method to get the User Info, and update the log, here.
     */
    public function shutdown(Event $event)
    {
        if (in_array($this->controller->request->action, ['logout', 'login'])) {
            return;
        }
        $entity = $this->savedLog;
        $userInfo['user_id'] = $this->getAuthInfo();
        $entity = $this->table->patchEntity($entity, $userInfo);
        $isSaved = $this->table->updateLog($entity);
        if (!$isSaved) {
            return false;
        }

        return true;
    }

    /**
     * logging logging method will call savelog in AccessLogsTable.
     *
     * @param [type] $saveArray [description]
     *
     * @return [type] [description]
     */
    protected function logging($saveArray)
    {
        $this->savedLog = $this->table->saveLog($saveArray);
        if ($this->savedLog) {
            return true;
        }

        return false;
    }

    /**
     * specialLog 外部から呼び出すやつ。
     *
     * @param [type] $code [description]
     *
     * @return [type] [description]
     */
    public function specialLog($code)
    {
        $saveArray['code'] = $code;

        return $this->table->updateLog($saveArray);
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

        $returnArray[$this->client_ip] = $this->getGlobalIp();
        $returnArray[$this->action_name] = $this->getRequestInfo('action');
        $returnArray[$this->controller_name] = $this->getRequestInfo('controller');
        $returnArray[$this->pass_name] = $this->getRequestInfo('pass');
        $returnArray[$this->query] = $this->getRequestParams('query');
        $returnArray[$this->data] = $this->getRequestParams('data');

        return $returnArray;
    }

    /**
     * getAuthInfo method to get the auth user id.
     *
     * @return [type] auth user id
     */
    protected function getAuthInfo()
    {
        if (!$this->ignoreChecker('user_id')) {
            return;
        }
        $user = $this->controller->Auth->user();
        if (!isset($user['id'])) {
            return;
        }

        return $user['id'];
    }

    /**
     * getGlobalIp the ip address who accessed the page.
     *
     * @return ip address
     */
    protected function getGlobalIp()
    {
        if (!$this->ignoreChecker('client_ip')) {
            return;
        }

        return $this->request->clientIp(false);
    }

    /**
     * get the request info.
     *
     * @return [array] json encoded params
     */
    protected function getRequestInfo($param)
    {
        if (!$this->ignoreChecker($param)) {
            return;
        }
        $requestParams = $this->controller->request->params;
        if (!isset($requestParams[$param])) {
            return false;
        }
        if (is_array($requestParams)) {
            return json_encode($requestParams[$param]);
        }

        return $requestParams[$param];
    }

    /**
     * getRequestParams.
     *
     * @return [type] [description]
     */
    protected function getRequestParams($param)
    {
        if (!$this->ignoreChecker($param)) {
            return;
        }
        if (empty($this->request->{$param})) {
            return;
        }
        $request = $this->request->{$param};
        if (is_array($request)) {
            $request = $this->blakclistChecker($request);

            return json_encode($request);
        }

        return $request;
    }

    /**
     * ignoreChecker
     * check ignore list.
     *
     * @param [type] $string [column name]
     *
     * @return [bool]
     *
     * if $string is settled in ['ignore'], return false
     * else return true
     */
    protected function ignoreChecker($string)
    {
        if (!isset($this->_defaultConfig['ignore'])) {
            return true;
        }

        if (!in_array($string, $this->_defaultConfig['ignore'])) {
            return true;
        }

        return false;
    }

    /**
     * unset blacklist from array.
     *
     * @return array
     */
    protected function blakclistChecker($array)
    {
        if (!isset($this->_defaultConfig['blacklist'])) {
            return $array;
        }

        $blacklist = $this->_defaultConfig['blacklist'];
        foreach ($array as $key => $value) {
            if (in_array($key, $blacklist)) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
