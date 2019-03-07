<?php
/**
 * Created by PhpStorm.
 * User: DreamWake
 * Date: 2018/12/27
 * Time: 20:05
 */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'model/baseModel.php');


/**
 * 章节发布类
 * Class ChannelRelease
 * @package meizizi\inc
 */
class ChannelRelease {
    /**
     * - 发布任务表模型
     * - 发布详情表模型
     *
     * - 发布方式
     * - 发布渠道
     * - 发布作品ID
     *
     *
     *   - 发布章节ID
     *   - 截止章节ID
     *   - 设置定时发布时间
     *   - 选择发布星期
     *   - 发布状态
     */
    private $model;
    private $taskList;
    private $logFile;

    public function __construct() {
        $this->logFile = $logFile = '/home/search/meizizi/error_log/auto_release_'.date('Y-m-d').'.log';
        $this->model = new BaseModel();

        // 查找未完成的任务
        $sql = sprintf("select * from section_auto_release where `state`=0");
        $this->taskList = $this->model->ExecuteRead($sql);
    }

    /**
     * 执行自动发布
     */
    public function release() {
        if(empty($this->taskList))
            return ;

        foreach ($this->taskList as $task){
            switch ($task['release_method']){
                case self::RELEASE_METHOD_AUTO:
                    $this->autoRelease($task);
                    break;
                case self::RELEASE_METHOD_FIX_TIME:
                    $this->fixTimeRelease($task);
                    break;
                default:


            }
        }
    }

    /**
     * 自动发布
     * @param $task
     */
    private function autoRelease($task) {
        // 检查当天是否发布 避免当天多次重复运行重复发布
        if($this->isTodayReleased($task)) {
            // var_dump('今日已执行此任务');
            return;
        }

        $logMsg = date('Y-m-d H:i:s').':'.PHP_EOL
            .'任务详情:'.json_encode($task);
        $this->writeLog($logMsg);

        // 当天有定时发布任务不进行自动发布
        $haveFixtimeTask = $this->isHaveFixtimeTask($task['cartoon_id'],$task['thirduser_id']);
        if($haveFixtimeTask){
            $this->addReleaseRecord($task);
            $logMsg = date('Y-m-d H:i:s').':'.PHP_EOL
                .'有定时发布任务，未执行此次自动发布';
            $this->writeLog($logMsg);
        }

        $now = time();
        $todayWeekNum = date('N',$now);
        $weeks = json_decode($task['release_weeks'],true);
        foreach ($weeks as $week){
            // 到星期$week就执行发布 $task['count_per_day']话
            if($week != $todayWeekNum)
                continue;

            $lastSort = $this->getLastReleaseSort($task['cartoon_id'],$task['thirduser_id']);
            if(0 == $lastSort)
                continue;

            // 检查存稿是否满足这天发布需要
            if(!$this->checkAllowance($task['cartoon_id'],$lastSort,$task['end_section_id'],$task['count_per_day'])) {
                // 更改自动发布状态 为暂停
                $this->updateReleaseStatus($task['id'],self::RELEASE_STATE_PAUSE,'存稿不够已暂停自动发布');
                continue;
            }

            for ($i = 1; $i <= $task['count_per_day']; $i++) {
                // 在某渠道 发布某作品的下一话
                $this->releaseSectionBySort($task['cartoon_id'],$task['thirduser_id'],++$lastSort);
            }
        }

        // 插入发布记录
        $this->addReleaseRecord($task);
    }


    /**
     * 查询是否当天有自动发布任务
     * @param $cartoonId
     * @param $thirduserId
     * @return bool
     */
    private function isHaveFixtimeTask($cartoonId,$thirduserId){
        $sql = sprintf("select * from section_auto_release "
            ."where `thirduser_id`=%d and `cartoon_id`=%d",$thirduserId,$cartoonId);
        $res = $this->model->ExecuteRead($sql);
        if($res)
            return true;

        return false;
    }

    /**
     * 定时发布
     * @param $task
     */
    private function fixTimeRelease($task) {
        $now = time();
        // 定时发布
        if(empty($task['set_time'])){
            $this->updateReleaseStatus($task['id'],self::RELEASE_STATE_FAIL,'定时发布时间为空');
            return ;
        }

        if(strtotime($task['set_time']) > $now)
            return ;

        // 进行发布处理
        $releaseRes = $this->releaseSectionBySectionId($task['cartoon_id'],$task['thirduser_id'],$task['section_id']);
        if($releaseRes)
            $this->updateReleaseStatus($task['id'],self::RELEASE_STATE_SUCCESS);
        else
            $this->updateReleaseStatus($task['id'],self::RELEASE_STATE_FAIL,'发布失败');

        // 插入发布记录
        $this->addReleaseRecord($task);
    }

    /**
     * 发布某序号的章节到某平台
     * @param $cartoonId
     * @param $thirduserId
     * @param $sort
     * @return int
     */
    private function releaseSectionBySort($cartoonId, $thirduserId, $sort) {
        // 手动授权、渠道有效(未过授权期限、未被删除)、授权记录有效
        $sql = sprintf("SELECT tucid,a.tuid,tuname,tucsectionlist,tucsectiontimelist,tuauthenddate FROM `thirduserandcartooninfos` a LEFT JOIN thirduserinfos b ON a.tuid=b.tuid WHERE ctid = %d AND a.tuid=%d AND tuctype=5 AND tuauthenddate>CURDATE() AND tucstate<>400 AND tustate<>400;",$cartoonId,$thirduserId);
        $released = $this->model->ExecuteRead($sql);
        // print_r($released);exit;

        // 可能授权过期 可能之前无手动授权章节 都不应该继续发布
        if(empty($released))
            return 0;
        $releasedSections = json_decode($released[0]['tucsectionlist'],true);
        $releasedTimeList = json_decode($released[0]['tucsectiontimelist'],true);

        // 按顺序号查找章节ID
        $section = $this->getSectionBySort($cartoonId,$sort);
        // var_dump($section);
        if(is_null($section)){
            return false;
        }

        if(is_null($releasedTimeList)){
            $releasedTimeList[] = [
                'ctsid' => $section['section_id'],
                'time' => date('Y-m-d H:i:s')
            ];
        }else{
            array_push($releasedTimeList,[
                'ctsid' => $section['section_id'],
                'time' => date('Y-m-d H:i:s')
            ]);
        }

        array_push($releasedSections,$section['section_id']);
        // 添加章节数据到已发布
        $logMsg = '发布'.$section['section_id'].'|$thirduserId:'.$thirduserId;
        $this->writeLog($logMsg);
        return $this->updateReleasedSection(json_encode($releasedSections),json_encode($releasedTimeList),$thirduserId,$cartoonId);
    }

    private function writeLog($logMsg) {
        error_log($logMsg.PHP_EOL,3,$this->logFile);
    }

    /**
     * 发布某序号的章节到某平台
     * @param $cartoonId
     * @param $thirduserId
     * @param $sectionId
     * @return int
     */
    private function releaseSectionBySectionId($cartoonId, $thirduserId, $sectionId) {
        // 手动授权、渠道有效(未过授权期限、未被删除)、授权记录有效
        $sql = sprintf("SELECT tucid,a.tuid,tuname,tucsectionlist,tucsectiontimelist,tuauthenddate FROM `thirduserandcartooninfos` a LEFT JOIN thirduserinfos b ON a.tuid=b.tuid WHERE ctid = %d AND a.tuid=%d AND tuctype=5 AND tuauthenddate>CURDATE() AND tucstate<>400 AND tustate<>400;",$cartoonId,$thirduserId);
        $released = $this->model->ExecuteRead($sql);
        // print_r($released);exit;

        // 可能授权过期 可能之前无手动授权章节 都不应该继续发布
        if(empty($released))
            return 0;
        $releasedSections = json_decode($released[0]['tucsectionlist'],true);
        $releasedTimeList = json_decode($released[0]['tucsectiontimelist'],true);
        if(is_null($releasedTimeList)){
            $releasedTimeList[] = [
                'ctsid' => $sectionId,
                'time' => date('Y-m-d H:i:s')
            ];
        }else{
            array_push($releasedTimeList,[
                'ctsid' => $sectionId,
                'time' => date('Y-m-d H:i:s')
            ]);
        }

        array_push($releasedSections,$sectionId);
        // 添加章节数据到已发布
        return $this->updateReleasedSection(json_encode($releasedSections),json_encode($releasedTimeList),$thirduserId,$cartoonId);
    }

    /**
     * 更新发布状态
     * @param $taskId
     * @param $status
     * @param string $msg
     */
    private function updateReleaseStatus($taskId,$status,$msg = '') {
        $updateTime = date('Y-m-d H:i:s');
        if(!empty($msg))
            $msg = sprintf(",error_msg = '%s'",$msg);
        $sql = sprintf("update section_auto_release set `state`= %d%s,`update_time`='%s' where `id` = %d",$status,$msg,$updateTime,$taskId);
        $this->model->ExecuteSql($sql);
    }


    /**
     * 获取上次发布章节的顺序号
     * @param $cartoonId
     * @param $thirduserId
     * @return int
     */
    private function getLastReleaseSort($cartoonId,$thirduserId) {
        // 规定使用自动发布前 一定要存在手动授权过的章节 避免查不到上次发布章节顺序号的情况考虑
        // 手动授权、渠道有效(未过授权期限、未被删除)、授权记录有效
        $sql = sprintf("SELECT tucid,a.tuid,tuname,tucsectionlist,tuauthenddate FROM `thirduserandcartooninfos` a LEFT JOIN thirduserinfos b ON a.tuid=b.tuid WHERE ctid = %d AND a.tuid=%d AND tuctype=5 AND tuauthenddate>CURDATE() AND tucstate<>400 AND tustate<>400;",$cartoonId,$thirduserId);
        $released = $this->model->ExecuteRead($sql);
        // print_r($released);exit;

        // 可能授权过期 可能之前无手动授权章节 都不应该继续发布
        if(empty($released))
            return 0;
        $releasedSections = json_decode($released[0]['tucsectionlist'],true);
        $lastReleaseSection = (int)array_pop($releasedSections);
        if(0 == $lastReleaseSection)
            return 0;

        return $this->getSortBySectionId($lastReleaseSection);
    }

    /**
     * 获取某章节的章节顺序号
     * @param $sectionId
     * @return int
     */
    private function getSortBySectionId($sectionId) {
        $sql = "SELECT ctssort FROM cartoonsectioninfos WHERE ctsid =".$sectionId;
        $sortRes = $this->model->ExecuteRead($sql);
        return (int)$sortRes[0]['ctssort'];
    }

    /**
     * 检查存稿是否充足
     * @param $cartoonId
     * @param $lastSort
     * @param $endSectionId
     * @param $needCount
     * @return bool
     */
    private function checkAllowance($cartoonId,$lastSort,$endSectionId,$needCount) {
        $sections = $this->getCartoonSections($cartoonId);

        // 检查是否到截止章节
        if(!empty($task['end_section_id'])){
            $overEndSection = $this->isOverEndSection($endSectionId,$lastSort,$needCount);
            if($overEndSection){
                // 超过截止章节
                $this->writeLog('$cartoonId,$lastSort,$endSectionId,$needCount:'."$cartoonId,$lastSort,$endSectionId,$needCount".'|超过截止章节');
                return false;
            }
        }

        // 取最新章节作为截止章节 再检查存稿是否充足
        $newestSection = array_pop($sections);//
        if(empty($newestSection))
            return false;
        // var_dump($newestSection);
        $overEndSection = $this->isOverEndSection($newestSection['section_id'],$lastSort,$needCount);
        if($overEndSection){
            // 存稿不够
            $this->writeLog('$cartoonId,$lastSort,$endSectionId,$needCount:'."$cartoonId,$lastSort,$endSectionId,$needCount".'|存稿不够');
            return false;
        }

        if(self::RELEASE_NON_ONEKEYED){
            // 允许发布未一键发布的章节的情况
            return true;
        }else{
            // 不允许发布未一键发布的章节的情况 逐一检查接下来的某章是否已发布成功
            for ($i = 1; $i <= $needCount; $i++) {
                $section = $this->getSectionBySort($cartoonId,$lastSort+$i,$sections);
                if(empty($section)){
                    // 顺序号中断
                    $this->writeLog('$cartoonId,$lastSort,$endSectionId,$needCount:'."$cartoonId,$lastSort,$endSectionId,$needCount".'|顺序号中断');
                    return false;
                }

                $onekeyReleased = $this->checkOneKeyReleased($cartoonId,$section['section_id'],self::ONEKEY_RELEASE_CHECK_PLAT);
                if(!$onekeyReleased){
                    // 未一键发布过
                    $this->writeLog('$cartoonId,$lastSort,$endSectionId,$needCount:'."$cartoonId,$lastSort,$endSectionId,$needCount".'|未一键发布过');
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 检查某章节是否一键发布成功过
     * @param $cartoonId
     * @param $sectionId
     * @param string $platId 可指定检查到某平台的发布
     * @return bool
     */
    private function checkOneKeyReleased($cartoonId,$sectionId,$platId = '') {
        if(!empty($platId))
            $platId = "AND pfid=".$platId;

        // 上传成功、审核通过即代表一键发布成功
        $sql = sprintf("SELECT ctrrid from cartoonreleaserecordinfos WHERE ctid=%d AND ctsid = %d %s AND ctrrstate IN (25,30) LIMIT 1",$cartoonId,$sectionId,$platId);
        $onkeyRes = $this->model->ExecuteRead($sql);
        if(empty($onkeyRes))
            return false;

        return true;
    }

    /**
     * 检查是否超过截止章节的限制(只根据顺序号大小比较 不保证顺序号的连续性)
     * @param int $endSectionId  截止章节ID
     * @param int $lastSort 上一次发布章节顺序号
     * @param int $needCount 接下来需要连续发布章节数int
     * @return bool
     */
    private function isOverEndSection($endSectionId,$lastSort,$needCount) {
        $endSectionSort = $this->getSortBySectionId($endSectionId);
        $needLastReleaseSort = $lastSort+$needCount;
        if($needLastReleaseSort > $endSectionSort)
            return true;

        return false;
    }

    /**
     * 更新发布章节
     * @param $releasedSections
     * @param $releasedTimes
     * @param $thirduserId
     * @param $cartoonId
     * @return bool|resource
     */
    private function updateReleasedSection($releasedSections,$releasedTimes,$thirduserId,$cartoonId) {
        $updateTime = date('Y-m-d H:i:s');
        $sql = sprintf("update thirduserandcartooninfos set tucsectionlist = '%s',tucsectiontimelist='%s',tucupdatetime='%s' WHERE tuid=%d AND ctid=%d",$releasedSections,$releasedTimes,$updateTime,$thirduserId,$cartoonId);
        // var_dump($sql);

        return $this->model->ExecuteSql($sql);
    }

    /**
     * 根据顺序号查找章节数据
     * @param $cartoonId
     * @param $sort
     * @param array $sections 可查找已存在的章节array里的某顺序号的章节数据
     * @return mixed|null
     */
    private function getSectionBySort($cartoonId,$sort,$sections = []) {
        // 排除非通用版 已删除数据
        if(empty($sections))
            $sections = $this->getCartoonSections($cartoonId);

        if(empty($sections))
            return null;
        foreach ($sections as $section){
            if($sort == $section['cartoon_sort']){
                return $section;
            }
        }

        return null;
    }

    /**
     * 查询某作品章节
     * section_id
     * cartoon_name
     * cartoon_sort
     * cartoon_cover
     *
     * @param $cartoonId
     * @return array|bool
     */
    private function getCartoonSections($cartoonId) {
        $sql = sprintf("SELECT ctsid as section_id,ctsname as cartoon_name,ctssort as cartoon_sort,ctscover as cartoon_cover FROM cartoonsectioninfos "
            ."WHERE ctid=%d AND ctsstate!=400 AND ctsstate=40 AND ctsparentid=0 "
            ."ORDER BY ctssort",$cartoonId);
        $sectionRes = $this->model->ExecuteRead($sql);
        return $sectionRes;
    }

    /**
     * 返回当日是否完成此任务
     * @param $task
     * @return bool
     */
    private function isTodayReleased($task) {
        $sql = sprintf("select * from section_auto_release_record where `task_id`=%d and release_time like '%s'",$task['id'],date('Y-m-d').'%');
        $res = $this->model->ExecuteRead($sql);
        if($res)
            return true;

        return false;
    }

    /**
     * 插入发布记录
     * @param $task
     * @return bool|resource
     */
    private function addReleaseRecord($task) {
        $sql = sprintf("insert into  section_auto_release_record(`task_id`,`release_time`) value(%d,'%s') ",$task['id'],date('Y-m-d H:i:s'));
        return $this->model->ExecuteSql($sql);
    }

    // 发布模式
    const RELEASE_METHOD_AUTO = 1;
    const RELEASE_METHOD_FIX_TIME = 2;

    // 发布状态 0待发布 1正在发布 2发布成功 3失败 4暂停(存量不足或其他意外情况)  400删除状态
    const RELEASE_STATE_WAITING = 0;
    const RELEASE_STATE_DOING = 1;
    const RELEASE_STATE_SUCCESS = 2;
    const RELEASE_STATE_FAIL = 3;
    const RELEASE_STATE_PAUSE = 4;
    const RELEASE_STATE_DELETED = 400;

    // 是否发布未一键发布的章节
    const RELEASE_NON_ONEKEYED = 0;//
    const ONEKEY_RELEASE_CHECK_PLAT = '';//检查章节是否已一键发布以这个平台为准
}