<?php
namespace App\Modules\Api\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiBaseController;
use Illuminate\Support\Facades\Config;
use Validator;
use Toplan\PhpSms\Sms;
use Illuminate\Support\Facades\Crypt;
use App\Modules\User\Model\UserModel;
use App\Modules\Manage\Model\UserSystemTaskModel;
use App\Modules\Manage\Model\SystemTasksModel;
use App\Modules\Manage\Model\UserSystemOptionModel;
use App\Modules\Manage\Model\UserSystemTaskChangeModel;
use App\Modules\Manage\Model\UserRodeFeeModel;
use App\Modules\User\Model\UserBalanceModel;
use Cache;
use DB;
use Socialite;
use Auth;
use Log;

class SystemTaskController extends ApiBaseController
{
    private $special_task;

    public function __construct(Request $request)
    {
        $this->tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        //设定获取不同等级的任务的概率: (19-普通任务  20-黄金任务  21-钻石任务)  15-普通任务  16-黄金任务  17-钻石任务
        // $this->probability = array('19'=>'7000','20'=>'2000','21'=>'1000');
        $this->probability = array('15'=>'7000','16'=>'2000','17'=>'1000');
        //用户系统任务状态 1-进行中 2-已完成 3-已失效  4-已领取
        $this->ust_status = array('1'=>'进行中','2'=>'已完成','3'=>'已失效','4'=>'已领取');
        //用户每天可以领取的任务次数
        $this->per_day_select_times = 3;
        //用户路费获取规则 时间限制单位 天
        $this->rode_rule = array(
                // 'calcul' => '8/10',
                'limit_time'=>'60',
                // 'status' => array('1' => '进行中','2' => '已领取','3' => '已失效'),
                'status' => array('1' => '进行中','2' => '已完成','3' => '已失效','4' => '已领取'),
            );
        $this->special_task = array(
            '200' =>[5000,3000,1000],
            '500' =>[20000,20000,10000],
            '1000'=>[50000,'iphonex']
        );
    }

    //用户获取系统任务选项
    public function getOption(Request $request)
    {
        //验证用户目前是否有正在进行的系统任务
        $user_system_task = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','1')->count();
        if($user_system_task) return $this->formateResponse(2000,'该用户有正在进行中的系统任务');

        $user_system_option = UserSystemOptionModel::where('uid',$this->tokenInfo['uid'])->where('status','1')->first();
        if(!empty($user_system_option)){
            $user_option = explode(',',$user_system_option['systerm_task_id_arr']);
            $returnArr = [];
            foreach($user_option as $option){
                $returnArr[] = SystemTasksModel::where('id',$option)
                ->select('id','name','content','amount','time_limit','reward_type','reward_amount')
                ->first()->toArray();
            }
            return $this->formateResponse(1000,'您的选项为',$returnArr);
        }

        DB::beginTransaction();
        //更换之前判断一下是否超过可以领取次数
        $userSystemTaskChange = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])
        ->select('today_select_times','updated_at')->first();

        if(!$userSystemTaskChange)
        {
            $u_s_t_c_res = UserSystemTaskChangeModel::create(['uid'=>$this->tokenInfo['uid']]);
            if(!$u_s_t_c_res) return $this->formateResponse(1001,'网络错误');
        }
        elseif(substr($userSystemTaskChange['updated_at'],0,10) < date('Y-m-d'))
        {
            $u_s_t_c_res = UserSystemTaskChangeModel::update(['change_num'=>'0','today_select_times'=>'0']);
            if(!$u_s_t_c_res){
                DB::rollBack();
                return $this->formateResponse(1001,'网络错误');
            }
        }
        elseif($userSystemTaskChange['today_select_times'] > $this->per_day_select_times 
            || $userSystemTaskChange['today_select_times'] == $this->per_day_select_times)
        {
            return $this->formateResponse(1001,'每日最多领取3次任务哦');
        }

        //产生3个待选择系统任务选项
        $grade = [];
        for($i=0;$i<3;$i++){
            $grade[] = self::getRand($this->probability);
        }

        $task_ids = "";
        foreach($grade as $grade_single){
            $system_tasks = SystemTasksModel::where('grade',$grade_single)->select('id')->get();
            $system_task_id_arr = array_flatten($system_tasks);
            $key = array_rand($system_task_id_arr,1);
            if(!empty($task_ids)){
                $task_ids = $task_ids.','.$system_task_id_arr[$key]['id'];
            }else{
                $task_ids = $system_task_id_arr[$key]['id'];
            }
            $tasks[] = SystemTasksModel::where('id',$system_task_id_arr[$key]['id'])
                            ->where('status','valid')
                            ->select('id','name','content','amount','time_limit','reward_type','reward_amount')
                            ->get();
        }

        $data = array(
                'uid' => $this->tokenInfo['uid'],
                'systerm_task_id_arr' => $task_ids,
                'status' => '1'
            );
        $u_s_o_res = UserSystemOptionModel::create($data);

        if(!$u_s_o_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }

        return $this->formateResponse(1000,'来选择您的任务吧',$tasks);
    }

    //用户选择任务
    public function userSelect(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'task_id' => 'required',
        ],[
            'task_id.required' => '参数错误',
        ]);
        $error = $validator->errors()->all();
        if(count($error)) return $this->formateResponse(1001,$error[0], $error);

        $system_task = SystemTasksModel::where('id',$request->get('task_id'))->first();
        if($system_task['status'] == 'Invalid') return $this->formateResponse(1001,'抱歉，该系统任务已失效');

        $user_system_task = UserSystemOptionModel::where('uid',$this->tokenInfo['uid'])->where('status',1)->first();
        $tasks = explode(',',$user_system_task['systerm_task_id_arr']);
        if(!in_array($request->get('task_id'),$tasks)) return $this->formateResponse(1001,'请选择有效的系统任务');

        $userSystemTaskChange = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])
                                    ->select('today_select_times')->first();
        if($userSystemTaskChange['today_select_times'] > $this->today_select_times 
            || $userSystemTaskChange['today_select_times'] == $this->today_select_times) 
        {
            return $this->formateResponse(1001,'每日最多领取3次任务哦');
        }

        $time_limit = $system_task->time_limit;
        DB::beginTransaction();
        $data = array(
                'uid' => $this->tokenInfo['uid'],
                'need_num' => $system_task->amount,
                'completed_num' => '0',
                'deadline' => date("Y-m-d H:i:s",strtotime("+".$time_limit." days")),
                'systerm_task_id' => $request->get('task_id'),
                'systerm_task_type' => $system_task['type'],
                'status' => '1',
            );

        $u_s_t_res = UserSystemTaskModel::create($data);
        if(!$u_s_t_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }

        $u_s_o_res = UserSystemOptionModel::where('uid',$this->tokenInfo['uid'])->where('status',1)->update(['status'=>2]);
        if(!$u_s_o_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }

        //每日选择任务次数更改
        $u_s_t_c_res = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])->increment('today_select_times','1');
        if(!$u_s_t_c_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }

        DB::commit();
        return $this->formateResponse(1000,'请开始您的任务之旅吧');
    }

    //用户的系统任务列表
    public function userSystemTasks(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'page' => 'required',
            'per_page' => 'required',
        ],[
            'page.required' => '请选择页码',
            'per_page.required' => '请选择每页条数'
        ]);
        $error = $validator->errors()->all();
        if(count($error)) return $this->formateResponse(1001,$error[0], $error);

        $ustcount = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->count();
        if($ustcount>0){
            $per_page = $request->get('per_page');
            $page = $request->get('page');
            $start = $per_page*($page-1);
            $userSystemTasks = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                                        ->skip($start)
                                                        ->take($per_page)
                                                        ->orderBy('created_at','desc')
                                                        ->select('need_num','completed_num','deadline','systerm_task_id','status','created_at','id')
                                                        ->get()
                                                        ->toArray();

            $flag = false;
            if($userSystemTasks){
                foreach($userSystemTasks as &$task_single){
                    if(strtotime($task_single['deadline']) < time() && $task_single['status'] == 1){
                        $u_s_t_res = UserSystemTaskModel::where('id',$task_single['id'])->update(['status'=>'3']);
                        if(!$u_s_t_res){
                            $flag = true;
                            break;
                        }
                        $task_single['status'] = 3;
                    }
                    $task_single['state'] = $this->ust_status[$task_single['status']];
                    $system_task = SystemTasksModel::where('id',$task_single['systerm_task_id'])->select('name','content')->first();
                    $task_single['system_task_name'] = $system_task['name'];
                    $task_single['system_task_content'] = $system_task['content'];
                }
                if($flag) return $this->formateResponse(1001,'网络错误');
                return $this->formateResponse(1000,'success',$userSystemTasks);
            }else{
                return $this->formateResponse(2000,'无更多数据');
            }
        }else{
            return $this->formateResponse(3000,'暂无数据');
        }
        return $this->formateResponse(2000,'暂无系统任务');
    }
    //领取奖励
    public function recive(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'user_system_task_id' => 'required',
        ],[
            'user_system_task_id.required' => '请选择需领取的任务',
        ]);
        $error = $validator->errors()->all();
        if(count($error)) return $this->formateResponse(1001,$error[0], $error);

        $user_system_task = UserSystemTaskModel::where('id',$request->get('user_system_task_id'))->where('status',2)->first();
        if(!$user_system_task) return $this->formateResponse(1001,'请选择正确的任务信息');

        if($user_system_task['need_num'] == $user_system_task['completed_num'])
        {
            $system_task = SystemTasksModel::where('id',$user_system_task['systerm_task_id'])
                                            ->select('reward_type','reward_amount')->first();
            DB::beginTransaction();
            if($system_task['reward_type'] == 1){
                //余额
                $u_b_res = UserBalanceModel::addUserBalance($system_task['reward_amount'],$this->tokenInfo['uid'],'8');
            }elseif($system_task['reward_type'] == 2){
                //金币
                $u_b_res = UserBalanceModel::addUserGold($system_task['reward_amount'],$this->tokenInfo['uid'],'','3');
            }
            if(!$u_b_res){
                DB::rollBack();
                return $this->formateResponse(1001,'网络错误');
            }

            $u_s_t_res = UserSystemTaskModel::where('id',$request->get('user_system_task_id'))->update(['status'=>'4']);
            if(!$u_s_t_res){
                DB::rollBack();
                return $this->formateResponse(1001,'网络错误');
            }

            DB::commit();
            return $this->formateResponse(1000,'领取成功');
        }
        return $this->formateResponse(1001,'任务未完成');
    }

    public function changePay(Request $request)
    {
        $validator = Validator::make($request->all(),[
            // 'pay_code' => 'required',
            'pay_amount' => 'required',
        ],[
            // 'pay_code.required' => '请输入支付密码',
            'pay_amount.required' => '请输入支付金额',
        ]);
        $error = $validator->errors()->all();
        if(count($error)) return $this->formateResponse(1001,$error[0], $error);

        //验证用户支付费用
        $userSystemTaskChange = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])->select('change_num')->first();
        $fee = UserSystemTaskChangeModel::changeFee($userSystemTaskChange['change_num']);
        if($fee != $request->get('pay_amount')) return $this->formateResponse(1001,'支付金额有误');

        $user = UserModel::where('id',$this->tokenInfo['uid'])->select('salt')->first();
        // $pay_code = UserModel::encryptPassword($request->get('pay_code'), $user['salt']);

        DB::beginTransaction();
        // $u_s_b_res = UserBalanceModel::addUserBalance($request->input('pay_amount'),$this->tokenInfo['uid'],9,'2',$pay_code);
        $u_s_b_res = UserBalanceModel::addUserBalance($request->input('pay_amount'),$this->tokenInfo['uid'],9,'2');
        if($u_s_b_res['code'] != 200){
            DB::rollBack();
            return $this->formateResponse(1001,$u_s_b_res['msg']);
        }

        $change = UserSystemTaskChangeModel::change($this->tokenInfo['uid']);
        if($change['code'] != 200){
            DB::rollBack();
            return $this->formateResponse(1001,$change['msg']);
        }else{
            DB::commit();
            return $this->formateResponse(1000,'success');
        }
    }

    public function changeFee(Request $request)
    {
        $user_system_task_change = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])
                                                            ->select('change_num','updated_at')
                                                            ->first();
        $last_time = substr($user_system_task_change['updated_at'],'0','10');
        $today = date('Y-m-d');
        $u_s_t_c_count = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])->count();
        DB::beginTransaction();
        if(!$u_s_t_c_count){
            $create = UserSystemTaskChangeModel::create(['uid'=>$this->tokenInfo['uid']]);
            if(!$create){
                DB::rollBack();
                return $this->formateResponse(1001,'网络错误');
            }
            $fee = 0;
        }
        if($last_time < $today){
            $u_s_t_c_res = UserSystemTaskChangeModel::where('uid',$this->tokenInfo['uid'])->update(['change_num'=>'0','today_select_times'=>'0']);
            if($u_s_t_c_res === false){
                DB::rollBack();
                return $this->formateResponse(1001,'网络错误');
            }
            $fee = 0;
        }else{
            $time = $user_system_task_change['change_num'] + 1;
            $fee = UserSystemTaskChangeModel::changeFee($time);
        }
        DB::commit();
        return $this->formateResponse(1000,'success',array('fee'=>$fee));
    }

    //----------------------------领取邀请好友累积送好礼  与  系统任务（历练不同时进行）-----------------------------


    /**
     * 打开页面获取邀请固定人数任务(post:/cumulative/getList)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTheTask(Request $request)
    {
        UserSystemTaskModel::systemTasksCompleted(8,10);

        $user_system_task_ids = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','!=','3')->lists('systerm_task_id')->toArray();//接受任务
        $system_task_ids = SystemTasksModel::where('type','10')->where('status','valid')->lists('id')->toArray();
        $user_promote_num = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','!=','3')->orderBy('id','asc')->pluck('completed_num');

        //dd($user_promote_num);
        if(empty($system_task_ids)){
            return $this->formateResponse('1001','暂无该任务活动');
        }

        $param = array_diff($system_task_ids,$user_system_task_ids);

        if(!empty($param)){
            $system_tasks = SystemTasksModel::whereIn('id',$param)->where('type','10')->where('status','valid')->get()->toArray();

            foreach($system_tasks as $s_t_single)
            {
                $time_limit = $s_t_single['time_limit'];
                $deadline = date('Y-m-d H:i:s',strtotime('+'.$time_limit.' days'));
                $user_system_task[] = array(
                    'uid' => $this->tokenInfo['uid'],
                    'systerm_task_id' => $s_t_single['id'],
                    'systerm_task_type' => '10',
                    'need_num' => $s_t_single['amount'],
                    'completed_num' =>empty($user_promote_num) ? 0 : $user_promote_num,
                    'deadline' => $deadline,
                    'status' => '1',
                    'change_read' => '2',
                    'change_num' => '0'
                );
            }

            $u_s_t_res = UserSystemTaskModel::insert($user_system_task);
            if(!$u_s_t_res){
                return $this->formateResponse(1001,'网络错误');
            }
        }

        $user_system_task = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','!=','3')->where('remark',0)->get()->toArray();

        $change = '0';
        $task = array();
        foreach ($user_system_task as &$u_s_t_s)
        {
            $system_tasks = SystemTasksModel::whereId($u_s_t_s['systerm_task_id'])->first();
            $u_s_t_s['system_task_amount'] =$system_tasks->reward_amount;

            if(strtotime($u_s_t_s['deadline']) < time() && $u_s_t_s['status'] != 3){
                $overArr[] = $u_s_t_s['id'];
                $u_s_t_s['status'] = 3;
            }

            if(($u_s_t_s['need_num'] == $u_s_t_s['completed_num'] || $u_s_t_s['completed_num'] > $u_s_t_s['need_num'])
                && $u_s_t_s['status'] != 4){
                $completedArr[] = $u_s_t_s['id'];
                $u_s_t_s['status'] = 2;
            }

            $u_s_t_s['state'] = $this->ust_status[$u_s_t_s['status']];

            if($u_s_t_s['change_num'] > 0 && $u_s_t_s['change_read'] == 1){
                $change = '1';break;
            }

            if($u_s_t_s['need_num'] == 200 || $u_s_t_s['need_num'] == 500 || $u_s_t_s['need_num'] == 1000 ){
                $task[] = $u_s_t_s;
            }
        }

        if(isset($overArr)){
            $u_s_t_res = UserSystemTaskModel::whereIn('id',$overArr)->update(['status'=>'3']);
            if(!$u_s_t_res){
                return $this->formateResponse(1001,'网络错误');
            }
        }

        if(isset($completedArr)){
            $u_s_t_res = UserSystemTaskModel::whereIn('id',$completedArr)->update(['status'=>'2']);
            if(!$u_s_t_res){
                return $this->formateResponse(1001,'网络错误');
            }
        }

        foreach($task as $k=>&$v){
            if($v['need_num'] == 200){
                $v['ditail'][0]['money'] = $this->special_task[200][0];
                $v['ditail'][1]['money'] = $this->special_task[200][1];
                $v['ditail'][2]['money'] = $this->special_task[200][2];
            }
            if($v['need_num'] == 500){
                $v['ditail'][0]['money'] = $this->special_task[500][0];
                $v['ditail'][1]['money'] = $this->special_task[500][1];
                $v['ditail'][2]['money'] = $this->special_task[500][2];
            }
            if($v['need_num'] == 500 || $v['need_num'] == 200){
                $v['ditail'][0]['status'] = 0;
                $v['ditail'][1]['status'] = 0;
                $v['ditail'][2]['status'] = 0;
            }
            if($v['need_num'] == 1000){
                $v['ditail'][0]['money'] = $this->special_task[1000][0];
                $v['ditail'][0]['status'] = 0;
            }
        }

        $user_system_task_specials = UserSystemTaskModel::where('status','!=','3')->where('remark','!=',0)->orderBy('need_num','asc')->orderBy('remark','asc')->get();

        //$ultimate_prize = array();
        if(isset($task[2])){
            $ultimate_prize = $task[2];
        }

        if(!$user_system_task_specials->isEmpty()){
            foreach($user_system_task_specials as $k=>$user_system_task_special){

                if($user_system_task_special->systerm_task_id == $task[0]['systerm_task_id']){//200
                    $user_system_receive = UserSystemTaskModel::where('status','!=','3')->where('need_num',$task[0]['need_num'])->where('remark','!=',0)->where('uid',$this->tokenInfo['uid'])->orderBy('need_num',200)->first();
                    $task[0]['status'] = 1;
                    if(!is_null($user_system_receive)){
                        $task[0]['status'] = $user_system_receive->status;
                    }
                    $task[0]['ditail'][$k]['remark'] = $user_system_task_special->remark;
                    $task[0]['ditail'][$k]['status'] = $user_system_task_special->status;
                    $task[0]['special_task'][] = $user_system_task_special->status;
                }

                if($user_system_task_special->systerm_task_id == $task[1]['systerm_task_id']){//500
                    $user_system_receive = UserSystemTaskModel::where('status','!=','3')->where('need_num',$task[1]['need_num'])->where('remark','!=',0)->orderBy('need_num',500)->where('uid',$this->tokenInfo['uid'])->first();
                    $task[1]['status'] = 1;
                    if(!is_null($user_system_receive)){
                        $task[1]['status'] = $user_system_receive->status;
                    }
                    $task[1]['ditail'][$k]['remark'] = $user_system_task_special->remark;
                    $task[1]['ditail'][$k]['status'] = $user_system_task_special->status;
                    $task[1]['special_task'][] = $user_system_task_special->status;
                    //dd($task);
                }

                if(isset($task[2]) && $user_system_task_special->systerm_task_id == $task[2]['systerm_task_id']){//1000
                    $user_system_receive = UserSystemTaskModel::where('status','!=','3')->where('need_num',$task[2]['need_num'])->where('remark','!=',0)->orderBy('need_num','asc')->where('uid',$this->tokenInfo['uid'])->first();
                    $task[2]['status'] = 1;
                    if(!is_null($user_system_receive)){
                        $task[2]['status'] = $user_system_receive->status;
                    }
                    $task[2]['ditail'][0]['remark'] = $user_system_task_special->remark;
                    $task[2]['ditail'][0]['status'] = $user_system_task_special->status;
                    $task[2]['special_task'][] = $user_system_task_special->status;
                    $ultimate_prize = $task[2];
                }
            }
        }

        if(isset($task[2])){
            unset($task[2]);
        }

        foreach($task as &$v){
            if(isset($v['special_task'])){
                $count = count($v['special_task']);
                if(($v['need_num'] == 200 || $v['need_num'] == 500) && $count >= 3) {
                    $array = array_unique($v['special_task']);
                    if($array[0] == 4){
                        $v['all_status'] = 1;
                    }
                }
            }
        }

        if(isset($ultimate_prize) && $ultimate_prize['need_num'] == 1000 && $ultimate_prize['ditail'][0]['status'] == 4) {
            $ultimate_prize['all_status'] = 1;
            $user_system_task['task_ultimate_prize'] = $ultimate_prize;
        }

        $user_system_task['task_special'] = $task;
        $user_system_task['name'] = $this->tokenInfo['name'];

        return $this->formateResponse(1000,'success',array('user_system_task' => $user_system_task,'change'=>$change));
        /*//判断用户是否已经接受任务
        if($user_system_task->isEmpty())
        {
            unset($user_system_task);
            $user_system_task = [];
            //如果没有，就接收任务，返回任务信息   从 system_task表获取活动任务数据，给用户创建任务
            $system_task = SystemTasksModel::where('type','10')->where('status','valid')->get();

            if($system_task->isEmpty()){
                return $this->formateResponse('1001','暂无该任务活动');
            }

            $system_task = $system_task->toArray();

            foreach($system_task as $s_t_single)
            {
                $time_limit = $s_t_single['time_limit'];
                $deadline = date('Y-m-d H:i:s',strtotime('+'.$time_limit.' days'));
                $user_system_task[] = array(
                        'uid' => $this->tokenInfo['uid'],
                        'systerm_task_id' => $s_t_single['id'],
                        'systerm_task_type' => '10',
                        'need_num' => $s_t_single['amount'],
                        'completed_num' => '0',
                        'deadline' => $deadline,
                        'status' => '1',
                        'change_read' => '2',
                        'change_num' => '0'
                    );
            }

            $u_s_t_res = UserSystemTaskModel::insert($user_system_task);
            if(!$u_s_t_res){
                return $this->formateResponse(1001,'网络错误');
            }
        }else{
            //如果有，就直接返回任务信息
            $user_system_task = $user_system_task->toArray();
        }*/

    }

    /**
     * 输入回家路费领取任务(post:/rodeFee/getTask)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRodeFeeTask(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'rode_fee' => 'required|integer|min:2|max:2000',
        ],[
            'rode_fee.required' => '请填写路费',
            'rode_fee.integer' => '请填写整数',
            'rode_fee.min' => '金额大于1',
            'rode_fee.max' => '金额在2000以内',
        ]);
        $error = $validator->errors()->all();
        
        if(count($error)) {
            return $this->formateResponse(1001,$error[0], $error);
        }

        $today = date('Y-m-d');

       /* if($today < '2018-02-01' || $today > '2018-03-02'){
            return $this->formateResponse(1001,'任务已过期');
        }*/

        $user_rode_fee_task = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->count();

        if($user_rode_fee_task > 0){
            return $this->formateResponse(1001,'您已领取该任务');
        }

        if($request->get('rode_fee') < 0 || $request->get('rode_fee') == 0) {
            return $this->formateResponse(1001,'请输入大于 0 的金额');
        }

        // $need_num = $request->get('rode_fee') * $this->rode_rule['calcul'];
        $need_num = UserRodeFeeModel::calculNum($request->get('rode_fee'));
        //dd($need_num);
        if($need_num['code'] == -1){
            return $this->formateResponse(1001,$need_num['msg']);
        }
        $deadline = date('Y-m-d H:i:s',strtotime('+'.$this->rode_rule['limit_time'].'days'));

        //获取用户的回家路费任务
        $data = array(
                'uid' => $this->tokenInfo['uid'],
                'rode_fee' => $request->get('rode_fee'),
                'need_num' => $need_num['data'],
                'completed_num' => '0',
                'status' => '1',
                'change_read' => '2',
                'change_num' => '0',
                'deadline' => $deadline,
                'created_at' => date('Y-m-d H:i:s'),
            );

        $u_r_f_res = UserRodeFeeModel::create($data);

        $data['state'] = $this->rode_rule['status'][$data['status']];

        if(!$u_r_f_res) {
            return $this->formateResponse(1001,'网络错误');
        }else{
            return $this->formateResponse(1000,'success',array('data' => $data));
        }
    }

    /**
     * 用户是否已经获取路费任务(post:/rodeFee/ifGet)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    /*public function ifGetFeeTask(Request $request)
    {
        $user_rode_fee_task = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->count();

        if(!$user_rode_fee_task > 0){
            return $this->formateResponse(1001,'暂未参与该任务');
        }

        return $this->formateResponse(1000,'已参与该任务');
    }*/

    //展示用户的获取路费任务数据
    /**
     * (post:/rodeFee/getInfo)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rodeFeeTaskInfo(Request $request)
    {
        $user_rode_fee = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->first();

        if(is_null($user_rode_fee)){
            return $this->formateResponse(1001,'暂未参与该任务');
        }

        if(strtotime($user_rode_fee['deadline']) < time() && $user_rode_fee['status'] != 3){
            $u_r_f_res = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->update(['status'=>'3']);
            if(!$u_r_f_res){
                return $this->formateResponse(1001,'网络错误');
            }
            $user_rode_fee['status'] = 3;
        }

        if(($user_rode_fee['completed_num'] == $user_rode_fee['need_num'] || $user_rode_fee['completed_num'] > $user_rode_fee['need_num'])
             && $user_rode_fee['status'] != 4){
            $u_r_f_res = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->update(['status'=>'2']);
            if(!$u_r_f_res){
                return $this->formateResponse(1001,'网络错误');
            }
            $user_rode_fee['status'] = 2;
        }

        if($user_rode_fee['change_num'] > 0 && $user_rode_fee['change_read'] == '1'){
            $change = '1';
        }else{
            $change = '0';
        }

        $user_rode_fee['state'] = $this->rode_rule['status'][$user_rode_fee['status']];
        return $this->formateResponse(1000,'获取成功',array('user_rode_fee'=>$user_rode_fee,'change'=>$change));
    }

    /**
     * 用户领取邀请任务奖励(post:/cumulative/getReward)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTaskReward(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'system_task_id' => 'required|integer',
        ],[
            'system_task_id.required' => '请选择要领取的任务',
            'system_task_id.integer' => '请选择整数',
        ]);
        $error = $validator->errors()->all();

        if(count($error)) {
            return $this->formateResponse(1001,$error[0], $error);
        }

        $user_system_task = UserSystemTaskModel::where('user_system_tasks.uid',$this->tokenInfo['uid'])
                                               ->where('user_system_tasks.id',$request->get('system_task_id'))
                                               ->where('user_system_tasks.remark',0)
                                               ->leftJoin('system_tasks as st','st.id','=','user_system_tasks.systerm_task_id')
                                               ->select('user_system_tasks.status','user_system_tasks.completed_num','user_system_tasks.need_num','st.reward_type','st.reward_amount')
                                               ->first();

        if(empty($user_system_task)){
            return $this->formateResponse(1001,'无效的任务信息');
        }
        //判断任务是否有效，以及任务是否完成
        if($user_system_task['status'] == 4){
            return $this->formateResponse(1001,'该奖励已领取');
        }
        elseif($user_system_task['status'] == 3){
            return $this->formateResponse(1001,'该任务已失效');
        }

        if($user_system_task['completed_num'] < $user_system_task['need_num']){
            return $this->formateResponse(1001,'任务未完成');
        }

        $before_task = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                          ->where('need_num','<',$user_system_task['need_num'])
                                          ->orderBy('need_num','desc')
                                          ->select('need_num')
                                          ->first();

        $change_num = $user_system_task['need_num'];

        if(!empty($before_task)){
            $change_num = $change_num - $before_task['need_num'];
        }

        //如果完成则为用户发放奖励
        $data = array(
                'status' => '4',
                // 'receive_time' => date('Y-m-d H:i:s'),
            );

        DB::beginTransaction();

        $u_r_f_res = UserSystemTaskModel::where('id',$request->get('system_task_id'))->update($data);

        if(!$u_r_f_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }
        //奖励分类型：实物 OR 虚拟（ 按道理讲只有钱 ）
        if($user_system_task['reward_type'] == 1){
            //余额
            $u_b_res = UserBalanceModel::addUserBalance($user_system_task['reward_amount'],$this->tokenInfo['uid'],'8');
        }
        elseif($user_system_task['reward_type'] == 2){
            //金币
            $u_b_res = UserBalanceModel::addUserGold($user_system_task['reward_amount'],$this->tokenInfo['uid'],'','3');
        }
        else{
            DB::rollBack();
            return $this->formateResponse(1001,'奖励无效');
        }

        if(!$u_b_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }
        //领取邀请奖励，则减去回家路费人数
        $user_rode_fee = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->select('completed_num')->first();

        if(!empty($user_rode_fee)){
            $number = $user_rode_fee['completed_num'] - $change_num;
            if($number < 0) $number = 0;
            $update = array(
                    'completed_num' => $number,
                    'status' => '1',
                    'change_num' => $change_num,
                    'change_read' => '1',
                );
            $user_rode_fee = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->update($update);
        }

        DB::commit();
        return $this->formateResponse(1000,'领取成功');
    }

    /**
     * 领取特殊任务奖励(post:/cumulative/getTaskRewardSpecial)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTaskRewardSpecial(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'system_task_id' => 'required|integer',
        ],[
            'system_task_id.required' => '请要领取的任务',
            'system_task_id.integer' => '请选择整数',
        ]);
        $error = $validator->errors()->all();

        if(count($error)) {
            return $this->formateResponse(1001,$error[0], $error);
        }

        $user_system_task = UserSystemTaskModel::where('user_system_tasks.uid',$this->tokenInfo['uid'])
                                                ->where('user_system_tasks.systerm_task_id',$request->get('system_task_id'))
                                                ->where('user_system_tasks.remark','!=',0)
                                                ->leftJoin('system_tasks as st','st.id','=','user_system_tasks.systerm_task_id')
                                                ->select('user_system_tasks.id','user_system_tasks.status','user_system_tasks.systerm_task_id','user_system_tasks.remark','user_system_tasks.completed_num','user_system_tasks.need_num','st.reward_type','st.reward_amount')
                                                ->first();
        //dd($user_system_task);
        if(empty($user_system_task)){
            return $this->formateResponse(1001,'您未达成此成就，请加油');
        }
        //判断任务是否有效，以及任务是否完成
        if($user_system_task['status'] == 4){
            return $this->formateResponse(1001,'该奖励已领取');
        }
        elseif($user_system_task['status'] == 3){
            return $this->formateResponse(1001,'该任务已失效');
        }

        if($user_system_task['completed_num'] < $user_system_task['need_num']){
            return $this->formateResponse(1001,'任务未完成');
        }

        $before_task = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                        ->where('need_num','<',$user_system_task['need_num'])
                        ->orderBy('need_num','desc')
                        ->select('need_num')
                        ->first();

        $user_system_task_public = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                    ->where('remark',0)->where('status','!=',3)->where('systerm_task_id',$user_system_task['systerm_task_id'])
                                    ->first();

        if(is_null($user_system_task_public)){
            UserSystemTaskModel::whereId($request->get('system_task_id'))->update(['status'=>UserSystemTaskModel::TASK_STATUS_FAIL]);
            return $this->formateResponse(1001,'该任务已失效');
        }

        $change_num = $user_system_task['need_num'];

        if(!empty($before_task)){
            $change_num = $change_num - $before_task['need_num'];
        }
        //dd($change_num);
        //如果完成则为用户发放奖励
        $data = array(
            'status' => '4',
            // 'receive_time' => date('Y-m-d H:i:s'),
        );

        //DB::beginTransaction();

        $u_r_f_res = UserSystemTaskModel::where('id',$user_system_task->id)->update(['status'=>4]);

        if(!$u_r_f_res){
            //DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }
        //奖励分类型：实物 OR 虚拟（ 按道理讲只有钱 ）
        //dd($user_system_task_public);
        if($user_system_task['reward_type'] == 1){
            //余额
            $key = $user_system_task->remark-1;
            if(($user_system_task['need_num'] == 200 || $user_system_task['need_num'] == 500) && $user_system_task_public->status == 4){
                $user_system_task['reward_amount'] = $this->special_task[$user_system_task['need_num']][$key] - $user_system_task['reward_amount'];
            }
            if(($user_system_task['need_num'] == 200 || $user_system_task['need_num'] == 500) && $user_system_task_public->status == 2){
                $user_system_task['reward_amount'] = $this->special_task[$user_system_task['need_num']][$key];
                UserSystemTaskModel::where('id',$user_system_task_public->id)->update(['status' => UserSystemTaskModel::TASK_STATUS_COMPLATE]);
            }
           //dd($user_system_task['reward_amount']);

            $u_b_res = UserBalanceModel::addUserBalance($user_system_task['reward_amount'],$this->tokenInfo['uid'],'8');
        }
        elseif($user_system_task['reward_type'] == 2){
            //金币
            $u_b_res = UserBalanceModel::addUserGold($user_system_task['reward_amount'],$this->tokenInfo['uid'],'','3');
        }
        else{
            DB::rollBack();
            return $this->formateResponse(1001,'奖励无效');
        }

        if(!$u_b_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }
        //领取邀请奖励，则减去回家路费人数
        if($user_system_task_public->status == 2){
            $user_rode_fee = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->select('completed_num')->first();

            if(!empty($user_rode_fee)){
                $number = $user_rode_fee['completed_num'] - $change_num;
                if($number < 0) $number = 0;
                $update = array(
                    'completed_num' => $number,
                    'status' => '1',
                    'change_num' => $change_num,
                    'change_read' => '1',
                );
                $user_rode_fee = UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->update($update);
            }
        }

        DB::commit();
        return $this->formateResponse(1000,'领取成功');
    }

    //用户领取回家路费
    public function getRodeFee(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'user_rode_fee_id' => 'required|integer',
        ],[
            'user_rode_fee_id.required' => '请要领取的任务',
            'user_rode_fee_id.integer' => '请选择整数',
        ]);
        $error = $validator->errors()->all();

        if(count($error)) {
            return $this->formateResponse(1001,$error[0], $error);
        }

        $user_rode_fee = UserRodeFeeModel::where('id',$request->get('user_rode_fee_id'))
                                         ->where('uid',$this->tokenInfo['uid'])
                                         ->first();

        if(empty($user_rode_fee)){
            return $this->formateResponse(1001,'无效的任务信息');
        }

        if($user_rode_fee['status'] == 4){
            return $this->formateResponse(1001,'该任务已领取');
        }
        elseif($user_rode_fee['status'] == 3){
            return $this->formateResponse(1001,'该任务已失效');
        }

        if($user_rode_fee['completed_num'] < $user_rode_fee['need_num']){
            return $this->formateResponse(1001,'任务尚未完成');
        }

        DB::beginTransaction();

        $u_r_f_res = UserRodeFeeModel::where('id',$request->get('user_rode_fee_id'))->update(['status'=>UserRodeFeeModel::TASK_STATUS_SUCCESS]);

        //这里的奖励就只有钱了
        $u_b_res = UserBalanceModel::addUserBalance($user_rode_fee['rode_fee'],$this->tokenInfo['uid'],'14');

        if(!$u_b_res){
            DB::rollBack();
            return $this->formateResponse(1001,'网络错误');
        }
        //领取回家路费，则减去邀请奖励
        $user_system_task = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                               ->whereIn('status',array('1','2'))
                                               ->select('completed_num')
                                               ->first();

        if(!empty($user_system_task)){
            $number = $user_system_task['completed_num'] - $user_rode_fee['need_num'];
            if($number < 0)  $number = 0;
            $update = array(
                    'completed_num' => $number,
                    'change_num' => $number,
                    'change_read' => '1',
                );
            $u_s_t_res = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                            ->update($update);

            if(!$u_s_t_res){
                DB::rollBack();
                return $this->formateResponse(1001,'网络错误');
            }

            $us = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                             ->where('status','2')
                                             ->count();

            if($us > 0){
                $u_s_t_res1 = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])
                                                 ->where('status','2')
                                                 ->update(['status'=>'1']);

                if(!$u_s_t_res1){
                    DB::rollBack();
                    return $this->formateResponse(1001,'网络错误');
                }
            }  
        }

        DB::commit();
        return $this->formateResponse(1000,'领取成功');
    }

    /**
     * 累计邀请人数(post:)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function userPromoteNum(Request $request)
    {
        $user_system_detail = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->first();
        if(is_null($user_system_detail)){
            $promote_num = 0;
        }else{
            $promote_num = $user_system_detail['completed_num'];
        }

        return $this->formateResponse(1000,'success',$promote_num);
    }

    //----------------------------以下所有代码为测试使用，正常代码请写上面，以防误删-----------------------------
    /**
     * (post:/rodeFee/initialize)
     * @param Request $request
     */
    public function initialize(Request $request)
    {
        UserRodeFeeModel::delSomeThingByUid($this->tokenInfo['uid']);
        UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->delete();
        return $this->formateResponse(1000,'已删除');
    }

    //测试阶段加人数
    /**(post:/rodeFee/addPormo)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function addPormo(Request $request)
    {
        $num = $request->input('num',1);
        //UserModel::where('id',$this->tokenInfo['uid'])->increment('promote_num',$num);
        UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->increment('completed_num',$num);
        $user_system_task = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','1')->where('systerm_task_type',10)->first();

        $count_low = UserSystemTaskModel::where('remark','!=',0)->where('need_num',200)->count();
        if($count_low < 3 && $user_system_task['completed_num'] > 200){
            $user_system_special_task_low = UserSystemTaskModel::where('remark','!=',0)->where('need_num',200)->where('uid',$this->tokenInfo['uid'])->first();
            if(is_null($user_system_special_task_low)){
                $user_system_task_low = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','1')->where('systerm_task_type',10)
                    ->where('remark',0)->where('need_num',200)
                    ->select('uid','systerm_task_id','systerm_task_type','completed_num','need_num','deadline','change_read','change_num')
                    ->first()->toArray();
                $user_system_task_low['status'] = 2;
                $user_system_task_low['remark'] = $count_low + 1;
                UserSystemTaskModel::insert($user_system_task_low);
            }
        }

        $count_med = UserSystemTaskModel::where('remark','!=',0)->where('need_num',500)->count();
        if($count_med < 3 && $user_system_task['completed_num'] > 500){
            $user_system_special_task_low = UserSystemTaskModel::where('remark','!=',0)->where('need_num',500)->first();
            if(is_null($user_system_special_task_low)){
                $user_system_task_low = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','1')->where('systerm_task_type',10)
                    ->where('remark',0)->where('need_num',500)
                    ->select('uid','systerm_task_id','systerm_task_type','completed_num','need_num','deadline','change_read','change_num')
                    ->first()->toArray();
                $user_system_task_low['status'] = 2;
                $user_system_task_low['remark'] = $count_med + 1;
                UserSystemTaskModel::insert($user_system_task_low);
            }
        }

        $count_high = UserSystemTaskModel::where('remark','!=',0)->where('need_num',1000)->count();
        if($count_high < 1 && $user_system_task['completed_num'] > 1000){
            $user_system_special_task_low = UserSystemTaskModel::where('remark','!=',0)->where('need_num',1000)->first();
            if(is_null($user_system_special_task_low)){
                $user_system_task_low = UserSystemTaskModel::where('uid',$this->tokenInfo['uid'])->where('status','1')->where('systerm_task_type',10)
                    ->where('remark',0)->where('need_num',1000)
                    ->select('uid','systerm_task_id','systerm_task_type','completed_num','need_num','deadline','change_read','change_num')
                    ->first()->toArray();
                $user_system_task_low['status'] = 2;
                $user_system_task_low['remark'] = $count_high + 1;
                UserSystemTaskModel::insert($user_system_task_low);
            }
        }

        UserRodeFeeModel::where('uid',$this->tokenInfo['uid'])->increment('completed_num',$num);

        return $this->formateResponse(1000,'已添加');
    }

    //----------------------------以下所有代码为测试使用，正常代码请写上面，以防误删-----------------------------
}
