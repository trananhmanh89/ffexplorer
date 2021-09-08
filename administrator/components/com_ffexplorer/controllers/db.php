<?php

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die('Restricted access');

class FfexplorerControllerDb extends BaseController
{
    public function deleteRecord()
    {
        $this->checkToken();

        $table = $this->input->get('table');
        $condition = @json_decode($this->input->get('condition', '', 'raw'));

        if (!$table) {
            $this->response('error', 'Table is empty');
        }

        if (!$condition) {
            $this->response('error', 'Condition is empty');
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)->delete($db->qn($table));
        
        foreach ($condition as $k => $v) {
            $query->where($db->qn($k) . '=' . $db->q($v));
        }

        try {
            $db->setQuery($query, 0, 1)->execute();
            $this->response('success', true);
        } catch (Exception $e) {
            $this->response('error', $e->getMessage());
        }
    }

    public function insertRecord()
    {
        $this->checkToken();

        $data = @json_decode($this->input->get('data', '', 'raw'));
        if (!$data || !is_array($data)) {
            $this->response('error', 'data missing');
        }

        $table = $this->input->get('table');
        if (!$table) {
            $this->response('error', 'table missing');
        }

        $db = Factory::getDbo();
        $columns = array();
        $values = array();
        foreach ($data as $item) {
            $columns[] = $item->name;
            $values[] = $db->q($item->value);
        }

        $query = $db->getQuery(true)
            ->insert($db->qn($table))
            ->columns($db->qn($columns))
            ->values(implode(',', $values));
       
        try {
            $db->setQuery($query)->execute();
            $this->response('success', true);
        } catch (Exception $e) {
            $this->response('error', $e->getMessage());
        }
    }

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
            $this->response('success', true);
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

        $query = "SELECT `COLUMN_NAME` AS `name`, 
                        `COLUMN_KEY` AS `key`, 
                        `COLUMN_DEFAULT` AS `default`, 
                        `COLUMN_TYPE` AS `type`,
                        `EXTRA` AS extra
            FROM information_schema.`columns`
            WHERE `table_schema` = '$dbName' AND `table_name` = '$name'";
        
        $columns = $db->setQuery($query)->loadObjectList();
        $columns = array_map(function($col) {
            $col->default = trim($col->default, "'");
            return $col;
        }, $columns);
        
        $query = $this->getListQuery($name)->select('COUNT(*)');
        $total = $db->setQuery($query)->loadResult();

        $query = $this->getListQuery($name)->select('*');
        $limit = 50;
        $offset = $limit * ($page - 1);
        $items = $db->setQuery($query, $offset, $limit)->loadObjectList();
        
        $data = array(
            'columns' => $columns,
            'total' => $total,
            'items' => $items,
        );

        $this->response('data', $data);
    }

    protected function getListQuery($table)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)->from($db->qn($table));

        $filterCol = $this->input->get('filterCol');
        $filterValue = $this->input->get('filterValue', '', 'raw');
        $filterMethod = $this->input->get('filterMethod', '', 'raw');

        if ($filterCol && $filterMethod) {
            switch ($filterMethod) {
                case 'like_both':
                    $query->where($db->qn($filterCol) . ' LIKE ' . $db->q('%' . $filterValue . '%'));
                    break;

                case 'like_start':
                    $query->where($db->qn($filterCol) . ' LIKE ' . $db->q($filterValue . '%'));
                    break;

                case 'like_end':
                    $query->where($db->qn($filterCol) . ' LIKE ' . $db->q('%' . $filterValue));
                    break;
                
                default:
                    $query->where($db->qn($filterCol) . '=' . $db->q($filterValue));
                    break;
            }
        }

        return $query;
    }

    public function tableList()
    {
        $this->checkToken();
        $config = Factory::getConfig();
        $dbName = $config->get('db');
        $prefix = $config->get('dbprefix');

        $db = Factory::getDbo();
        $query = "SELECT TABLE_NAME AS `name`,
                (DATA_LENGTH + INDEX_LENGTH) AS `size`
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '$dbName'
            AND TABLE_NAME LIKE '$prefix%'
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

    protected function response($type = 'success', $data = array())
    {
        die(@json_encode(array($type => $data)));
    }
}