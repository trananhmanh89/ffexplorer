<?php

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die('Restricted access');

class FfexplorerControllerDb extends BaseController
{
    public function saveNode()
    {
        $this->checkToken();

        $table = $this->input->get('table');
        $condition = @json_decode($this->input->get('condition', '', 'raw'));
        $column = $this->input->get('column');
        $value = $this->input->get('value', '', 'raw');

        if (!$table || !$condition || !$column) {
            $this->response('error', 'Input error');
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->qn($table))
            ->set($db->qn($column) . '=' . $db->q($value));
        
        $where = array();
        foreach ($condition as $k => $v) {
            $where[] = $db->qn($k) . '=' . $db->q($v);
        }
        $query->where($where);

        try {
            $db->setQuery($query, 0, 1)->execute();

            $query = $db->getQuery(true)
                ->select($db->qn($column))
                ->from($db->qn($table))
                ->where($where);

            $result = $db->setQuery($query, 0, 1)->loadResult();
            $this->response('result', $result);
        } catch (Exception $e) {
            $this->response('error', $e->getMessage());
        }

    }
    public function initTable()
    {
        $this->checkToken();

        $name = $this->input->get('name');
        if (!$name) {
            $this->response('error', 'Table name is empty');
        }

        $page = $this->input->getInt('page', 1);
        $page = $page ? $page : 1;

        $dbName = Factory::getConfig()->get('db');
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = '$dbName' 
            AND table_name = '$name'";
        
        $existed = $db->setQuery($query)->loadResult();
        if (!$existed) {
            $this->response('error', 'Table is not existed');
        }

        $query = "SELECT `COLUMN_NAME` AS `name`, `COLUMN_KEY` AS `key`
            FROM information_schema.`columns`
            WHERE `table_schema` = '$dbName' AND `table_name` = '$name'";
        
        $columns = $db->setQuery($query)->loadObjectList();
        
        $query = "SELECT COUNT(*) FROM $name";
        $total = $db->setQuery($query)->loadResult();
        
        $limit = 50;
        $offset = $limit * ($page - 1);
        $query = "SELECT * FROM $name LIMIT $offset, $limit";
        $items = $db->setQuery($query)->loadObjectList();
        
        $data = array(
            'columns' => $columns,
            'total' => $total,
            'items' => $items,
        );

        $this->response('data', $data);
    }

    public function tableList()
    {
        $this->checkToken();
        $config = Factory::getConfig();
        $dbName = $config->get('db');

        $db = Factory::getDbo();
        $query = "SELECT TABLE_NAME AS `name`,
                (DATA_LENGTH + INDEX_LENGTH) AS `size`
            FROM
                information_schema.TABLES
            WHERE
                TABLE_SCHEMA = '$dbName'
            ORDER BY `name` ASC";

        $list = $db->setQuery($query)->loadObjectList();

        $this->response('data', $list);
    }

    public function checkToken($method = 'post', $redirect = false)
    {
        // sleep(3);
        if (!parent::checkToken($method, $redirect)) {
            $this->response('error', 'csrf token error');
        }
    }

    protected function response($type = 'success', $data)
    {
        die(@json_encode(array($type => $data)));
    }
}