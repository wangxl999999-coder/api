<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LoginLog;

class LoginLogController extends Controller
{
    public function index()
    {
        $page = $this->getQuery('page', 1);
        $pageSize = $this->getQuery('page_size', 10);

        $params = [];
        if ($this->getQuery('username') !== null) {
            $params['username'] = $this->getQuery('username');
        }
        if ($this->getQuery('login_type') !== null && $this->getQuery('login_type') !== '') {
            $params['login_type'] = $this->getQuery('login_type');
        }
        if ($this->getQuery('status') !== null && $this->getQuery('status') !== '') {
            $params['status'] = $this->getQuery('status');
        }
        if ($this->getQuery('start_time') !== null) {
            $params['start_time'] = $this->getQuery('start_time');
        }
        if ($this->getQuery('end_time') !== null) {
            $params['end_time'] = $this->getQuery('end_time');
        }

        $logModel = new LoginLog();
        $result = $logModel->search($params, $page, $pageSize);

        return $this->success($result);
    }

    public function show($id)
    {
        $logModel = new LoginLog();
        $log = $logModel->find($id);

        if (!$log) {
            return $this->error(404, '日志不存在');
        }

        return $this->success($log);
    }

    public function destroy($id)
    {
        $logModel = new LoginLog();
        $log = $logModel->find($id);

        if (!$log) {
            return $this->error(404, '日志不存在');
        }

        $logModel->delete($id);

        return $this->success([], '删除成功');
    }

    public function batchDestroy()
    {
        $input = $this->requireInput(['ids']);
        $ids = $input['ids'];

        if (!is_array($ids) || empty($ids)) {
            return $this->error(400, '参数格式错误');
        }

        $logModel = new LoginLog();
        $count = 0;

        foreach ($ids as $id) {
            if ($logModel->delete($id)) {
                $count++;
            }
        }

        return $this->success(['deleted_count' => $count], '批量删除成功');
    }

    public function clear()
    {
        $days = $this->getQuery('days', 30);
        $days = (int)$days;

        $logModel = new LoginLog();
        $logModel->clear($days);

        return $this->success([], "已清理{$days}天前的日志");
    }

    public function statistics()
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
        $thisMonthStart = date('Y-m-d', strtotime('first day of this month'));

        $stats = [
            'today_count' => $this->getCountByDate($today),
            'yesterday_count' => $this->getCountByDate($yesterday),
            'this_week_count' => $this->getCountByDateRange($thisWeekStart, $today),
            'this_month_count' => $this->getCountByDateRange($thisMonthStart, $today),
            'success_count' => $this->getCountByStatus(1),
            'fail_count' => $this->getCountByStatus(0),
        ];

        return $this->success($stats);
    }

    public function chartData()
    {
        $days = $this->getQuery('days', 7);
        $days = (int)$days;

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data[] = [
                'date' => $date,
                'count' => $this->getCountByDate($date),
                'success' => $this->getCountByDateAndStatus($date, 1),
                'fail' => $this->getCountByDateAndStatus($date, 0)
            ];
        }

        return $this->success(['items' => $data]);
    }

    private function getCountByDate($date)
    {
        $sql = "SELECT COUNT(*) as count FROM login_logs WHERE DATE(created_at) = :date";
        $result = $this->db->fetch($sql, ['date' => $date]);
        return (int)$result['count'];
    }

    private function getCountByDateAndStatus($date, $status)
    {
        $sql = "SELECT COUNT(*) as count FROM login_logs WHERE DATE(created_at) = :date AND status = :status";
        $result = $this->db->fetch($sql, ['date' => $date, 'status' => $status]);
        return (int)$result['count'];
    }

    private function getCountByDateRange($startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) as count FROM login_logs WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
        $result = $this->db->fetch($sql, ['start_date' => $startDate, 'end_date' => $endDate]);
        return (int)$result['count'];
    }

    private function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM login_logs WHERE status = :status";
        $result = $this->db->fetch($sql, ['status' => $status]);
        return (int)$result['count'];
    }
}
