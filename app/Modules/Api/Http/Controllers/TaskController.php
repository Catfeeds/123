<?php


namespace App\Modules\Api\Http\Controllers;

use App\Http\Controllers\ApiBaseController;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\AgreementModel;
use App\Modules\Manage\Model\IconModel;
use App\Modules\Manage\Model\MessageTemplateModel;
use App\Modules\Manage\Model\PunishModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Order\Model\SubOrderModel;
use App\Modules\Task\Model\ServiceModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\Task\Model\TaskDelayModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskRightsModel;
use App\Modules\Task\Model\TaskWaterModel;
use App\Modules\Task\Model\WorkAttachmentModel;
use App\Modules\Task\Model\WorkCommentModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\CommentModel;
use App\Modules\Task\Model\DistrictRegionModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\UserBalanceModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\BrowesModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Manage\Model\UserSystemTaskModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\Api\Http\Controllers\TaskOtherController;
use Validator;
use Illuminate\Support\Facades\Crypt;
use DB;
use Lang;

class TaskController extends ApiBaseController
{
    protected $uid;
    protected $username;
    protected $mobile;

    public function __construct(Request $request)
    {
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $this->uid = $tokenInfo['uid'];
        $this->school = $tokenInfo['school'];
        $this->username = $tokenInfo['name'];
        $this->mobile = $tokenInfo['mobile'];

        //任务状态:1:审核中 2已发布 3经行中 4申请超时 5提交完成（猎人）6完成 7失败 8维权 9审核失败 10待付款
        $this->status = array('1'=>'审核中','2'=>'已发布','3'=>'进行中','4'=>'申请超时','5'=>'提交完成','6'=>'完成','7'=>'失败','8'=>'维权','9'=>'审核失败','10'=>'待付款');
    }

    /**任务列表(post:/tasks)
     * @param Request $request
     * @param $num 页数
     * @param $area 区域id
     * @param $limit 每页显示数量
     * @param cate_id 任务分类ID
     * @param bounty_start 赏金下限
     * @param bounty_end赏金上限
     * @param title标题
     * @return \Illuminate\Http\Response
     */
    public function getTaskList(Request $request)
    {
       /* if(empty($this->school)){
            return $this->formateResponse(1001,'请先完善信息');
        }*/
        $data = $request->all();
        $validator = Validator::make($data,[
            'limit' => 'required|integer|min:1',
        ],[
            'limit.required' => '请填写数量',
            'limit.integer' => '请输入整数',
            'limit.min' => '最小值为1',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $num = $request->input('page_num',1);
        $time = date("Y-m-d H:i:s");
        $offset = ($num - 1) * $data['limit'];
        $data['time'] = $request->input('time',1);
        $data['bounty_start'] = $request->input('bounty_start','');

        if($num == 1){
            $task = TaskModel::taskSelect($data);
            $date['taskDinmond'] = $task->where('top_status',TaskModel::TASK_DIAMAND)->where('publicity_at','>=',$time)
                                           ->whereStatus(TaskModel::TASK_PUB)->select('id','title','desc','bounty','created_at','uid')
                                           ->orderBy('real_cash','desc')->get();//钻石置顶任务

            $task = TaskModel::taskSelect($data);
            $date['taskTops'] = $task->where('top_status',TaskModel::TASK_TOP)->where('publicity_at','>=',$time)
                                      ->whereStatus(TaskModel::TASK_PUB)->select('id','title','desc','bounty','created_at','uid')
                                      ->orderBy('real_cash','desc')->get();//置顶任务
        }

        $task = TaskModel::taskSelect($data);
        $task_pub = $task->where('top_status',TaskModel::TASK_TOP_NULL)->where('publicity_at','>',$time)
                                  ->whereStatus(TaskModel::TASK_PUB)->select('id','publicity_at','title','desc','bounty','created_at','uid','identify')
                                  ->orderBy('id','desc')->offset($offset)->limit($data['limit'])->get();//普通任务

        //假数据
        $length = $data['limit'] - count($task_pub);
        $task_water = TaskWaterModel::taskSelect($data);
        $task_waters = $task_water->orderByRaw('RAND()')->whereStatus(TaskModel::TASK_PUB)->take($length)
                        ->select('id','publicity_at','title','desc','bounty','created_at','uid','identify','schoolName','avatar')->get()->toArray();

        $date['ads'] = IconModel::getIconInfoFromSort(IconModel::ICON_TYPE_AD,IconModel::INCO_AD_TASK);

        if(isset($date['taskDinmond']) && !$date['taskDinmond']->isEmpty()){
            foreach($date['taskDinmond'] as $keyy =>$value){
                $value->avatar = UserModel::whereId($value->uid)->pluck('head_img');
                $schoolID = UserDetailModel::where('uid',$value->uid)->pluck('school');
                $value->schoolName = DistrictRegionModel::getDistrictName($schoolID);
                $value->time = self::timeShow($value->created_at);
                $date['taskDinmond'][$keyy] = $value->toArray();
            }
        }

        if(isset($date['taskTops']) && !$date['taskTops']->isEmpty()){
            foreach($date['taskTops'] as $k=>$v){
                $v->avatar = UserModel::whereId($v->uid)->pluck('head_img');
                $schoolID = UserDetailModel::where('uid',$v->uid)->pluck('school');
                $v->schoolName = DistrictRegionModel::getDistrictName($schoolID);
                $v->time = self::timeShow($v->created_at);
                $date['taskTops'][$k] = $v->toArray();
            }
        }

        if(!$task_pub->isEmpty() && !$task_waters->isEmpty()){
            foreach($task_pub as $val){
                $val->avatar = UserModel::whereId($val->uid)->pluck('head_img');
                $schoolID = UserDetailModel::where('uid',$val->uid)->pluck('school');
                $val->schoolName = DistrictRegionModel::getDistrictName($schoolID);
                $val->time = self::timeShow($val->created_at);
            }
            $task_pub = $task_pub->toArray();
            foreach($task_waters as $task_water){
                $task_water->time = self::timeShow($val->created_at);
            }
            $task_waters = $task_waters->toArray();

            $date['taskPubs'] = array_merge($task_pub,$task_waters);
        }

        if($length == 0){
            foreach($task_pub as $key=>$val){
                $val->avatar = UserModel::whereId($val->uid)->pluck('head_img');
                $schoolID = UserDetailModel::where('uid',$val->uid)->pluck('school');
                $val->schoolName = DistrictRegionModel::getDistrictName($schoolID);
                $val->time = self::timeShow($val->created_at);
                $task_pub[$key] = $val->toArray();
            }
            $date['taskPubs'] = $task_pub;
        }

        if($data['limit'] == $length){
            foreach($task_waters as $task_water){
                $task_water->time = self::timeShow($task_water->created_at);
            }
            $task_waters = $task_waters->toArray();
            $date['taskPubs'] = $task_waters;
        }

        if($num == 1 && empty($date['taskPubs']) && $date['taskTops']->isEmpty() && $date['taskDinmond']->isEmpty()){
            return $this->formateResponse(2000,'暂无数据',$date);
        }

        if($num != 1 && empty($date['taskPubs'])){
            return $this->formateResponse(2000,'暂无数据',$date);
        }

        return $this->formateResponse(1000,'获取成功',$date);
    }

    /**任务详情（post:/taskDetial）
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTaskDetials(Request $request)
    {
        $task_id = $request->input('task_id',0);
        //$idd = $request->input('identify',1);
        if($task_id == 0){
            return $this->formateResponse(1001,'请填写任务id');
        }

        $array = explode("&",$task_id);
        $taskId = $array[0];
        $idd = $array[1];

        if($idd == 9){

            $data = TaskWaterModel::whereId($taskId)->first()->toArray();
            if($data['status'] == TaskWaterModel::TASK_DOING){
                return $this->formateResponse(1001,'无权限查看');
            }
            $data['work_time'] = ($data['work_time'] == '0000-00-00 00:00:00') ? '不限' : $data['work_time'];
            $data['created_at'] = self::timeShow($data['created_at']);
            $data['user_school'] = $data['schoolName'];
            $data['user_name'] = $data['username'];
            $data['user_head_img'] = $data['avatar'];
            $data['regin_limit'] = $data['region_limit'];
            if($data['region_limit'] == '0'){
                $data['regin_limit'] = '不限';
            }
        }else{
            $data = TaskModel::detail($taskId,$this->uid);

            if(isset($data['idenify']) && $data['idenify'] == -1){
                return $this->formateResponse(1001,'无权限查看');
            }
        }

        if($data['uid'] != $this->uid){
            $datas = array(
                    'uid' => $this->uid,
                    'task_id' => $taskId,
                    'status' => '1',
                    'type' => '1',
                    'add_time' => date('Y-m-d H:i:s'),
                );
            $bro_res = BrowesModel::create($datas);
        }


        return $this->formateResponse(1000,'success',$data);
    }

    /**创建任务列表（post:/tasks/createTaskShow）
     * @return \Illuminate\Http\Response
     */
    public function createTaskShow()
    {
        //判断是否能进入
        $time = strtotime("now");
        $punish = PunishModel::findByUid($this->uid);
        $userdetial = UserDetailModel::whereUid($this->uid)->first();
        $userBalance = UserBalanceModel::findByUid($this->uid);

        if(!is_null($punish) && strtotime($punish->penalty_time) >= $time){
            return $this->formateResponse(1001,'您处于处罚时间内，暂无法发布任务');
        }
        if(is_null($userBalance)){
            return $this->formateResponse(1001,'您的资产被冻结，暂无法选择增值服务');
        }
        if($userBalance->balance == 0){
            return $this->formateResponse(1001,'您的资产为0，暂无法发布任务');
        }
        if(is_null($userdetial->region) || is_null($userdetial->province) || is_null($userdetial->school)){
            return $this->formateResponse(1001,'我的基本信息不完整暂无法创建任务，请填写后继续');
        }

        $data['phone'] = $this->mobile;

        return $this->formateResponse(1000,'success',$data);
    }

    /**图片上传（post:task/upload）
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function uploadPic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required',
        ],[
            'avatar.required' => '请上传图片',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $pic = $request->avatar;
        $data = $upload_res = self::uploadByBase64($pic);
        return response()->json(['code'=> 1000,'message'=>'success','data'=>$data['url_path']]);

    }

    /**获取用户基本信息（get:/tasks/userInfo）
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function userInfo(Request $request)
    {
        $userDetail = UserDetailModel::where('uid',$this->uid)->select('region','province','school','uid')->first();
        if(is_null($userDetail)){
            return $this->formateResponse(1001,'用户信息不全');
        }
        if($userDetail->region == 0 || $userDetail->province == 0 || $userDetail->school == 0){
            return $this->formateResponse(1001,'用户信息不全');
        }

        $userDetail->school_name = DistrictRegionModel::getDistrictName($userDetail->school);
        $userDetail = $userDetail->toArray();
        return $this->formateResponse(1000,'success',$userDetail);

    }


    /**创建任务(post:/myTask/createTask)
     * @param title标题
     * @param desc描述
     * @param cate_id种类
     * @param bounty金额
     * @param worker_num人数
     * @param phone手机号
     * @param publicity_at显示时间
     * @param work_time执行时间
     * @param hunter_grade_limit等级限制
     * @param region_limit区域限制
     * @param deposit_cash已托管金额
     * @param region大区
     * @param province省
     * @param area学校
     * @return \Illuminate\Http\Response
     */
    public function createTask(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'title' => 'required|max:15',
            'desc' => 'required|max:100',
            'cate_id' => 'required|integer',
            'bounty' => 'required|numeric|min:1',
            'worker_num' => 'required|integer|max:1|min:1',
            'phone' => 'required',
            'publicity_at' => 'required|integer|min:24|max:72'
        ],[
            'title.required' => '请填写任务标题',
            'title.max' => '标题不能超过15字',

            'desc.required' => '请填写任务描述',
            'desc.max' => '任务描述不超过100字',

            'cate_id.required' => '请选择行业类型',
            'cate_id.integer' => '请填写整数',

            'bounty.required' => '请输入您的预算',
            'bounty.numeric' => '请输入正确的预算格式',
            'bounty.min' => '余额最少为1',

            'worker_num.required' => '参与人数不能为空',
            'worker_num.integer' => '参与人数必须为整形',
            'worker_num.min' => '参与人数只能为1人',
            'worker_num.max' => '参与人数只能为1人',

            'phone.required' => '请输入手机号',

            'publicity_at.required' => '显示时间不能为空',
            'publicity_at.integer' => '显示时间必须为整形',
            'publicity_at.min' => '显示时间最小不能低于24小时',
            'publicity_at.max' => '显示时间最大不能超过72小时',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $time = strtotime("now");
        $punish = PunishModel::findByUid($this->uid);
        if(!is_null($punish) && strtotime($punish->penalty_time) >= $time){
            return $this->formateResponse(1001,'您处于处罚时间内，暂无法发布任务');
        }

        $data['desc'] =  \CommonClass::removeXss($data['desc']);
        $data['title'] =  \CommonClass::removeXss($data['title']);
        $data['publicity_at'] = $request->input('publicity_at');
        $data['work_time'] = $request->input('work_time','0000-00-00 00:00:00');
        $data['hunter_grade_limit'] = $request->input('hunter_grade_limit',0);
        $data['region_limit'] = $request->input('region_limit',0);
        $data['deposit_cash'] = 0;
        $data['uid'] = $request->input('uid',$this->uid);
        $data['username'] = $request->input('username',$this->username);


       if(isset($data['region']) || isset($data['province']) || isset($data['area'])){
           $data['region_limit'] = 1;
       }

        $data = TaskModel::createTask($data);

        if(is_null($data)){
            return $this->formateResponse(1001,'网络错误');
        }

        $data = TaskModel::whereId($data['id'])->select('id','uid','bounty','title','desc')->first()->toArray();

        UserBalanceModel::where('user_id',$this->uid)->increment('create_task_num', 1);

        return $this->formateResponse(1000,'创建成功',$data);

    }

    /**取消任务雇主(post:/taskCancel)
     * @param Request $request
     * @param task_id任务id
     * @return \Illuminate\Http\Response
     */
    public function cancelCreateTask(Request $request)
    {
		
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',
        ],[
            'task_id.required' => '请填写任务Id',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $taskId = $request->input('task_id');
        $time = strtotime("now");
        $task = TaskModel::whereId($taskId)->first();
		
		
        if($task->status == TaskModel::TASK_FAIL){
            return $this->formateResponse(1001,'任务以失败，操作无效');
        }

        if($task->view_count == 0 && strtotime($task->created_at) > ($time - 5*60)){//verified_at
            if($task->status == 1 || $task->status == 2 || $task->status == 3){
                $orders = OrderModel::where('task_id',$taskId)->first();
                $data = SubOrderModel::findByOrderId($orders->id);
                UserBalanceModel::where('user_id',$this->uid)->increment('balance', $data['sum']);
                OrderModel::whereId($orders->id)->update(['status' => 1]);
                SubOrderModel::where('order_id',$orders->id)->update(['status' => 1]);

                $param = [
                    'action' => 7,
                    'pay_type' => 1,
                    'pay_account' => $this->mobile,
                    'pay_code' => OrderModel::randomCode($this->uid),
                    'cash' => $data['sum'],
                    'uid' => $this->uid
                ];
                FinancialModel::createOne($param);
            }

            TaskModel::whereId($taskId)->update(['status'=>TaskModel::TASK_FAIL]);

            return $this->formateResponse(1000,'取消成功',$task);
        }

        return $this->formateResponse(1001,'超过五分钟，不能取消');
    }

    /**取消做任务(post:/task/CancelReceiver)
     * @param Request $request
     * @param task_id任务id
     * @return \Illuminate\Http\Response
     */
    public function cancelReceiveTask(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',

        ],[
            'task_id.required' => '请输入任务id',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,'输入的信息有误', $error);
        }
        $taskId = $request->input('task_id');
        $time = strtotime("now");

        $task = TaskModel::where('status',TaskModel::TASK_DOING)->whereId($taskId)->first();
        if(is_null($task)){
            return $this->formateResponse(1001,'任务状态不符，无法取消');
        }

        $work = WorkModel::where('status','!=',WorkModel::WORK_CANCEL)->where('task_id',$task->id)->whereUid($this->uid)->first();
        if(is_null($work)){
            return $this->formateResponse(1001,'没有权限');
        }

        if(strtotime($work->created_at) + 5*60 < $time ){
            return $this->formateResponse(1001,'超过五分钟，不能取消');
        }

        TaskModel::whereId($taskId)->update(['status'=>TaskModel::TASK_PUB]);
        WorkModel::whereId($work->id)->update(['status'=> WorkModel::WORK_CANCEL]);
        $taskAttachment = WorkAttachmentModel::where('task_id',$taskId)->where('work_id',$work->id)->first();

        if(!is_null($taskAttachment)){
            AttachmentModel::whereId($taskAttachment->attachment_id)->update(['status'=>0]);
        }

        return $this->formateResponse(1000,'取消成功');

    }

    /**接收任务(post:/work/createWinBid)
     * @param Request $request
     * @param task_id任务id
     * @param identify
     * @return \Illuminate\Http\Response
     */
    public function createWinBidWork(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',
            'identify' => 'required'

        ],[
            'task_id.required' => '请输入任务id',
            'identify.required' => '请输入身份信息',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }
        if($data['identify'] == 9){
            TaskWaterModel::whereId($data['task_id'])->update(['status' => TaskWaterModel::TASK_DOING]);
            return $this->formateResponse(1001,'该任务已失效或被人抢先接走');
        }

        $time = strtotime("now");
        $punish = PunishModel::findByUid($this->uid);
        if(!is_null($punish) && strtotime($punish->penalty_time) >= $time){
            return $this->formateResponse(1001,'您处于处罚时间内，暂无法接受任务');
        }

        $task = TaskModel::findById($data['task_id']);

        $userGrade = UserBalanceModel::findByUid($this->uid);

        if(is_null($userGrade)){
            return $this->formateResponse(1001,'您的资产被冻结，暂无法接受任务');
        }
        if(is_null($task) || $task->status != 2){
            return $this->formateResponse(1001,'该任务已失效或被人抢先接走');
        }
        if($this->uid == $task->uid){
            return $this->formateResponse(1001,'您是任务发布者，不能接收任务');
        }
        //dd($task);
        if($task->work_time != '0000-00-00 00:00:00' && strtotime($task->work_time) < strtotime("now")){
            return $this->formateResponse(1001,'该任务已过期');
        }
        if($task->hunter_grade_limit > $userGrade->hunter_grade){
            return $this->formateResponse(1001,'您的猎人等级过低，暂不能接收该任务');
        }

        $data['uid'] = $this->uid;

        $work_cancel = WorkModel::whereUid($data['uid'])->where('task_id',$data['task_id'])->orderBy('id','desc')->where('status',WorkModel::WORK_CANCEL)->first();
        if(!is_null($work_cancel)){
            return $this->formateResponse(1001,'您提交的该任务延迟被雇主拒绝，暂不能接受该任务');
        }

        $work = WorkModel::whereUid($data['uid'])->where('task_id',$data['task_id'])->orderBy('id','desc')->first();
        if(!is_null($work)){
            return $this->formateResponse(1001,'您已进行相关操作，不能重复提交');
        }

        $data['workname'] = $this->username;

        DB::transaction(function () use($data,$task) {
            $data = WorkModel::workCreate($data);
            $messageVariableArr = [
                'username' => $task->users_name,
                'workname' => $this->username,
                'task_number' => $task->id,
                'task_title' => $task->title,
                'win_price' => $task->bounty,

            ];
            MessageTemplateModel::sendToUser($task->id, 'task_receive', $messageVariableArr, '', 1,2);
            // UserBalanceModel::where('user_id', $this->uid)->increment('work_task_num', 1);
        });
        return $this->formateResponse(1000,'接收任务成功');
    }


    /**交付任务(post:/work/createDelivery)
     * @param Request $request
     * @param task_id任务id
     * @param url图片url
     * @return \Illuminate\Http\Response
     */
    public function createDeliveryWork(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',

        ],[
            'task_id.required' => '请输入任务id',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $work = WorkModel::whereUid($this->uid)->where('task_id',$data['task_id'])->whereStatus(1)->first();

        if(is_null($work)){
            return $this->formateResponse(1001,'没有交付权限');
        }

        if(isset($data['image']) && !empty($data['image'])){
            $image = self::uploadByBase64($data['image']);
            $data['url'] = $image['url_path'];
            $data['user_id'] = $this->uid;
            $data['status'] = 1;
            $attachment = AttachmentModel::createOne($data);

            WorkAttachmentModel::createOne($data['task_id'],1,$attachment['id']);
            return $this->formateResponse(1000,'上传成功');
        }

        $ret = TaskModel::whereId($data['task_id'])->update(['status'=>TaskModel::TASK_RECIEIVER_TRUE,'checked_at'=>date("Y-m-d H:i:s")]);
        WorkModel::whereId($work->id)->update(['status'=>WorkModel::WORK_PUSH]);

        $task = TaskModel::whereId($data['task_id'])->first();
        $messageVariableArr = [
            'username' => $task->username,
            'workname' => $this->username,
        ];
        MessageTemplateModel::sendToUser($task->uid,'agreement_documents',$messageVariableArr,$work->uid,1,2);


        if($ret){
            return $this->formateResponse(1000,'提交成功');
        }
        return $this->formateResponse(1001,'网络错误');

    }

    /**任务验收(post:/work/deliveryAgree)
     * @param Request $request
     * @param work_id接收任务id
     * @param task_id任务id
     * @return \Illuminate\Http\Response
     */
    public function deliveryWorkAgree(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',
            'work_id' => 'required',

        ],[
            'task_id.required' => '请输入任务id',
            'work_id.required' => '接收任务id',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $work_id = intval($data['work_id']);
        $work = WorkModel::where('id',$work_id)->first();
        if(!$work){
            return $this->formateResponse(1001,'此稿件不存在');
        }
        $task = TaskModel::where('id',$work->task_id)->first();

        if($task->uid != $this->uid){
            return $this->formateResponse(1001,'你不是雇主，无权操作');
        }

        $task_right = TaskRightsModel::where('task_id',$work->task_id)->first();
        if(!is_null($task_right)){
            return $this->formateResponse(1001,'该任务已维权，不能进行相关操作');
        }

        $work = WorkModel::where('task_id',$work->task_id)->where('uid',$work->uid)->where('status',2)->first();
        if(is_null($work)){
            return $this->formateResponse(1001,'当前任务不具备验收资格或已提交');
        }

        $worker_num = $task->worker_num;

        $data['worker_num'] = $worker_num;

        $data['task_id'] = $work->task_id;
        $data['uid'] = $work->uid;
        $data['work_id'] = $work->id;

        $workModel = new WorkModel();
        $result = $workModel->workCheck($data);

        if($result){

            TaskModel::whereId($work->task_id)->update(['status'=>TaskModel::TASK_COMPLATE]);
            $messageVariableArr = [
                'task_title' => $task->title,
            ];
            MessageTemplateModel::sendToUser($task->uid,'task_finish',$messageVariableArr,$work->uid,1,2);

            // 猎人任务完成客户端调用
            $u_b_res = UserBalanceModel::contigencyCompleted($work->uid,"1");
            if(!$u_b_res) return $this->formateResponse(1001,'网络错误');
            //系统任务完成任务的调用  kppw_type 表ID : 12  不要删
            // $user_system_task = UserSystemTaskModel::where('uid',$work->id)->where('status','1')->where('systerm_task_type',12)->first();
            // if($user_system_task){
            //     $u_s_t_res = UserSystemTaskModel::completed($work->id,12);
            //     if($u_s_t_res['code'] != 200) return $this->formateResponse(1001,$u_s_t_res['msg']);
            // }
            return $this->formateResponse(1000,'验收成功');
        }else{
            return $this->formateResponse(1001,'网络错误');
        }
    }

    /**双方互评(post:/work/evaluate)
     * @param Request $request
     * @param task_id任务id’
     * @param comment内容
     * @param speed_score速度
     * @param quality_score质量
     * @param attitude_score态度
     * @param type等级
     * @return \Illuminate\Http\Response
     */
    public function evaluateCreate(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',
            'comment' => 'required',
            'speed_score' => 'required|max:5',
            'quality_score' => 'required|max:5',
            'attitude_score' => 'required|max:5',

        ],[
            'task_id.required' => '请输入任务id',
            'comment.required' => '请填写内容',

            'speed_score.required' => '速度评分',
            'speed_score.max' => '不能超过5分',

            'quality_score.required' => '质量评分',
            'quality_score.max' => '不能超过5分',

            'attitude_score.required' => '态度评分',
            'attitude_score.max' => '不能超过5分',

        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $work = WorkModel::where('task_id',$data['task_id'])->where('status',3)->first();
        $task = TaskModel::where('id',$data['task_id'])->first();
        $data['work_id'] = $work->id;

        if(!$work && $task->uid != $this->uid){
            return $this->formateResponse(1001,'你没有评价此稿件的权限');
        }

        $data['from_uid'] = $this->uid;
        $data['comment'] = e($data['comment']);

        if($work->uid == $this->uid)
        {
            $data['to_uid'] = $task->uid;
            $data['comment_by'] = 0;
        }
        if($task->uid == $this->uid)
        {
            $work = WorkModel::where('id',$data['work_id'])->first();
            $data['to_uid'] = $work->uid;
            $data['comment_by'] = 1;
        }

        $is_evaluate =  CommentModel::where('from_uid',$this->uid)
            ->where('task_id',$data['task_id'])->where('to_uid',$data['to_uid'])
            ->first();
        if($is_evaluate){
            return $this->formateResponse(1001,'你已评论过此稿件');
        }
        $data['created_at'] = date('Y-m-d H:i:s',time());

        $comment = CommentModel::create($data);

        $result = CommentModel::where('id',$comment['id'])->first();
        if($comment){
            return $this->formateResponse(1000,'评价成功',$result);
        }else{
            return $this->formateResponse(1001,'评价失败');
        }
    }

    /**任务申请延迟(post:/tasks/createTaskDelay)
     * @param Request $request
     * @param task_id任务id
     * @param time分钟
     * @return \Illuminate\Http\Response
     */
    public function delayTaskTime(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',
            'time' => 'required',
        ],[
            'task_id.required' => '请输入任务id',
            'time.required' => '请选择时间',
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $task = TaskModel::findById($data['task_id']);
        $work = WorkModel::where('task_id',$task->id)->whereUid($this->uid)->whereStatus(WorkModel::WORK_SELECTED)->first();

        if(is_null($work)){
            return $this->formateResponse(1001,'你没有申请延迟权限');
        }
        if($task->work_time == '0000-00-00 00:00:00'){
            return $this->formateResponse(1001,'申请失败，该任务无时间限制，不需申请延迟');
        }
        if($task->status != TaskModel::TASK_DOING){
            return $this->formateResponse(1001,'该任务已提交或处于其他状态，不能进行相关操作');
        }

        $TaskDelayModel = TaskDelayModel::where('worker_id',$this->uid)->where('task_id',$data['task_id'])->first();
        if(!is_null($TaskDelayModel)){
            return $this->formateResponse(1001,'您已申请过延时，请勿重复提交');
        }
        $data['worker_id'] = $this->uid;

        DB::transaction(function () use($task,$work,$data) {
            TaskModel::whereId($data['task_id'])->update(['status' => TaskModel::TASK_APPLY_DELAY]);
            TaskDelayModel::createOne($data);
            //发信息
            $messageVariableArr = [
                'username' => $task->username,
                'username2' => $work->workname,
                'tasktitle' => $task->title,
            ];
            MessageTemplateModel::sendToUser($task->uid, 'application_delay', $messageVariableArr,$work->uid, 1, 2);
        });

        return $this->formateResponse(1000,'申请成功');
    }

    /**雇主延迟态度选择(post:/tasks/TaskDelayStepOne)
     * @param Request $request
     * @param task_id
     * @param attitude0,1同意
     * @param deplay_id延迟id
     * @return \Illuminate\Http\Response
     */
    public function employAttitudeDelayTask(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'task_id' => 'required',
            'attitude' => 'required',
            'deplay_id' => 'required'
        ],[
            'task_id.required' => '请输入任务id',
            'attitude.required' => '请选择是否同意',
            'deplay_id.required' => '延迟Id',
        ]);
        //1确认0拒绝
        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }

        $task = TaskModel::findById($data['task_id']);
        if($task->uid != $this->uid){
            return $this->formateResponse(1001,'你没有操作延迟权限');
        }
        if($task->status != 4){
            return $this->formateResponse(1001,'任务状态错误');
        }

        $taskDelay = TaskDelayModel::where('task_id',$task->id)->whereId($data['deplay_id'])->whereStatus(1)->first();
        //dd($taskDelay);
        if(is_null($taskDelay)){
            return $this->formateResponse(1001,'对方没有申请或其他原因');
        }

        $work = WorkModel::where('status',WorkModel::WORK_SELECTED)->where('task_id',$data['task_id'])->first();

        if($data['attitude'] == 1){
            TaskDelayModel::whereId($data['deplay_id'])->update(['status'=>TaskDelayModel::TASK_DELAT_AGREE]);
            TaskModel::whereId($data['task_id'])->update(['status'=>TaskModel::TASK_DOING,'work_time'=>date("Y-m-d H:i:s",(strtotime($task->work_time) + $taskDelay->delay_time * 60))]);

            $messageVariableArr = [
                'username' => $work->username,
                'task_title' => $task->title,
            ];
            MessageTemplateModel::sendToUser($work->uid, 'consent_to_delay', $messageVariableArr,$task->uid, 1, 2);

            $taskDelays = TaskDelayModel::whereId($data['deplay_id'])->select('id','status','delay_time','task_id')->first()->toArray();
            return $this->formateResponse(1000,'已修改完成',$taskDelays);
        }

        if($data['attitude'] == 0){
            TaskDelayModel::whereId($data['deplay_id'])->update(['status'=>TaskDelayModel::TASK_DELAY_REFUEE]);

            $messageVariableArr = [
                'username' => $work->username,
                'task_title' => $task->title,
            ];
            MessageTemplateModel::sendToUser($work->uid, 'denial_of_delay', $messageVariableArr,$task->uid, 1, 2);

            $taskDelay = TaskDelayModel::whereId($data['deplay_id'])->select('id','status','delay_time','task_id')->first()->toArray();
            return $this->formateResponse(1000,'雇主拒绝',$taskDelay);
        }
    }

    /**雇主放弃任务或重新发布(post:/tasks/TaskDelayStepTwo)
     * @param Request $request
     * @param deplay_id
     * @param task_id
     * @param choosen
     * @return \Illuminate\Http\Response
     */
    public function cancelOrAgainPubTask(Request $request)
    {
        $deplayId = $request->input('deplay_id',0);
        $taskId = $request->input('task_id',0);
        $choosen = $request->input("choosen",0);//0放弃任务，1重新发布

        if($taskId == 0){
            return $this->formateResponse(1001,'请选择任务');
        }
        if($deplayId == 0){
            return $this->formateResponse(1001,'请选择任务');
        }

        $taskDelay = TaskDelayModel::whereId($deplayId)->first();
        $task = TaskModel::whereId($taskId)->first();

        if($task->uid != $this->uid){
            return $this->formateResponse(1001,'无权限操作');
        }

        if($choosen == 0){
            TaskModel::whereId($taskId)->update(['status'=>TaskModel::TASK_FAIL]);
            $orders = OrderModel::where('task_id',$taskId)->first();
            $data = SubOrderModel::findByOrderId($orders->id);
            $param = [
                'action' => 7,
                'pay_type' => 1,
                'pay_account' => $this->mobile,
                'pay_code' => OrderModel::randomCode($this->uid),
                'cash' => $data['sum'],
                'uid' => $this->uid
            ];
            FinancialModel::createOne($param);
            UserBalanceModel::where('user_id',$this->uid)->increment('balance',$data['sum']);

            $messageVariableArr = [
                'task_title' => $task->title,
                'reason' => '您主动放弃的原因',
            ];
            MessageTemplateModel::sendToUser($task->uid, 'task_failed', $messageVariableArr,'', 1, 1);

            return $this->formateResponse(1000,'已放弃任务');
        }

        if($taskDelay->status != 4 || $task['status'] != 4){
            return $this->formateResponse(1001,'任务状态错误');
        }

        TaskModel::whereId($task->id)->update(['status'=>2]);
        WorkModel::where('task_id',$task->id)->update(['status'=>-1]);
        return $this->formateResponse(1000,'已重新发布');

    }

    /**雇主维权(post:/work/deliveryRight)
     * @param Request $request
     * @param task_id
     * @param image
     * @param desc
     * @return \Illuminate\Http\Response
     */
    public function deliveryWorkRight(Request $request)
    {
        if(!$request->get('task_id') or !$request->get('desc')){
            return $this->formateResponse(1001,'传送参数不能为空');
        }
        $data = $request->except('token');
        $time = strtotime("now");
        $task_id = intval($data['task_id']);

        $task = TaskModel::where('id',$task_id)->first();
        $work = WorkModel::where('task_id',$task_id)->whereIn('status',[2,3])->first();

        if($task->uid != $this->uid){
            return $this->formateResponse(1001,'你不具备维权资格');
        }

        if($task->status == 6 && (strtotime($task->end_at) + 24*3600) < $time){
            return $this->formateResponse(1001,'已过24小时，不能维权');
        }

        if($task->status == 5 || $task->status == 6) {
            /* if($work->uid == $this->uid){
                 $data['role'] = 0;
                 $data['from_uid'] = $this->uid;
                 $data['to_uid'] = $task->uid;
             }*/
            if ($task->uid == $this->uid) {
                $data['role'] = 1;
                $data['from_uid'] = $this->uid;
                $data['to_uid'] = $work->uid;
            }
            $data['status'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s', time());
            $data['task_id'] = $task->id;
            $data['attachment_id'] = '';

            if (isset($data['image']) && is_array($data['image']) && !empty($data['image'])) {
                foreach ($data['image'] as $v) {
                    $param = array();
                    $param['url'] = $v;
                    $param['status'] = 2;
                    $param['user_id'] = $this->uid;
                    $ret = AttachmentModel::createOne($param);
                    $data['attachment_id'] = $data['attachment_id'] . ',' . $ret->id;
                }
            }
            $result = TaskRightsModel::rightCreate($data);

            $messageVariableArr = [
                'username' => $work->username,
                'username2' => $this->username,
                 'task_title' => $task->title,
            ];
            MessageTemplateModel::sendToUser($work->uid, 'task_failed', $messageVariableArr, 1, 1);

           /* if($work->if_account){
                $orders = OrderModel::where('task_id',$task->id)->first();
                $data = SubOrderModel::findByOrderId($orders->id);
                UserBalanceModel::where('user_id',$work->uid)->increment('balance_freeze',$data['sum']);
            }*/

            if ($result) {
                return $this->formateResponse(1000, '维权成功');
            } else {
                return $this->formateResponse(2002, '网络错误');
            }
        }else{
            return $this->formateResponse(1001,'提交或任务完成才能维权');
        }
    }




    /*public function getTaskList(Request $request)
    {
        $data = $request->all();
        $data['limit'] = (isset($data['limit'])&&$data['limit']) ? $data['limit'] : 15;
        $tasks = TaskModel::whereIn('task.status',[2,3,4,5,6,7,8,9,10,11])
            ->select('task.*','cate.name as cate_name')
            ->leftjoin('cate','task.cate_id','=','cate.id');
        if(isset($data['cate_id']) && $data['cate_id']){
            $tasks = $tasks->where('task.cate_id',$data['cate_id']);
        }
        if(isset($data['type']) && $data['type']){
            switch($data['type']){
                case 1:
                    $tasks = $tasks->orderBy('task.id','desc');
                    break;
                case 2:
                    $tasks = $tasks->orderBy('task.view_count','desc');
                    break;
                case 3:
                    $tasks = $tasks->orderBy('task.bounty','desc');
                    break;
                case 4:
                    $tasks = $tasks->orderBy('task.delivery_deadline','desc');
                    break;
            }
        }

        $tasks = $tasks->paginate($data['limit'])->toArray();
        if($tasks['total']){
            return $this->formateResponse(1000,'success',$tasks);
        }else{
            return $this->formateResponse(2001,'暂无对应搜索条件的结果');
        }
    }*/

    //我发布的任务
    // public function myPubTasks(Request $request)
    // {
    //     $data = $request->all();
    //     $data['limit'] = (isset($data['limit'])&&$data['limit']) ? $data['limit'] : 10;
    //     $tasks = TaskModel::select('task.*','cate.name as cate_name')
    //         ->leftjoin('cate','task.cate_id','=','cate.id')
    //         ->where('task.uid',$this->uid);

    //     if (isset($data['status']) && $data['status']){
    //         switch($data['status']){
    //             case 1:
    //                 $status = [3,4,6];
    //                 break;
    //             case 2:
    //                 $status = [5];
    //                 break;
    //             case 3:
    //                 $status = [7];
    //                 break;
    //             case 4:
    //                 $status = [8,9,10];
    //                 break;
    //             default:
    //                 $status = [2,3,4,5,6,7,8,9,10,11];
    //         }
    //         $tasks = $tasks->whereIn('task.status',$status);
    //     }

    //     $tasks = $tasks->where('task.status','>=',2)->where('task.status','<=',11)->orderBy('task.created_at','desc')->paginate($data['limit'])->toArray();
    //     if($tasks['total']){
    //         $status = [
    //                 2=>'审核中',
    //                 3=>'定时发布',
    //                 4=>'投稿中',
    //                 5=>'选稿中',
    //                 6=>'选稿中',
    //                 7=>'交付中',
    //                 8=>'待评价',
    //                 9=>'已结束',
    //                 10=>'已结束',
    //                 11=>'维权中'
    //         ];
    //         if(isset($data['status'])){
    //             $tasks['workStatus'] = $data['status'];
    //         } else{
    //             $tasks['workStatus'] = 0;
    //         }
    //         foreach($tasks['data'] as $k=>$v){
    //             $tasks['data'][$k]['status'] = $status[$v['status']];
    //         }
    //     }
    //     return $this->formateResponse(1000,'success',$tasks);
    // }

    /**我发布的任务(get:/myTask/index)
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myPubTasks(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'per_page' => 'required',
            'page' => 'required',
        ],[
            'per_page.required' => '参数不完整：per_page',
            'page.required' => '参数不完整：page',
        ]);
        $error = $validator->errors()->all();
        if(count($error)) return $this->formateResponse(1001, $error[0]);
        $tokenInfo = Crypt::decrypt(urldecode($request->input('token')));
        $page = $request->get('page');
        $per_page = $request->get('per_page');
        $start = $per_page*($page-1);
        $list = TaskModel::where('uid',$tokenInfo['uid'])
                        ->orderBy('created_at','desc')
                        ->select('id','title','created_at','status','identify')
                        ->skip($start)
                        ->take($per_page)
                        ->get();
        if(count($list)){
            foreach($list as &$list_single){
                $list_single['state'] = $this->status[$list_single['status']];
            }
            return $this->formateResponse(1000,'success',array('list'=>$list));
        }else{
            return $this->formateResponse(2000,'暂无数据');
        }
    }


    //我接受的任务
    // public function myAcceptTask(Request $request)
    // {
    //     $data = $request->all();
    //     $data['limit'] = (isset($data['limit'])&&$data['limit']) ? $data['limit'] : 15;
    //     $taskIDs = WorkModel::where('uid',$this->uid)->select('task_id')->distinct()->get()->toArray();
    //     if(count($taskIDs)){
    //         $tasks = TaskModel::whereIn('id',$taskIDs);
    //         if (isset($data['status']) && $data['status']){
    //             switch($data['status']){
    //                 case 1:
    //                     $status = [3,4,6];
    //                     break;
    //                 case 2:
    //                     $status = [5];
    //                     break;
    //                 case 3:
    //                     $status = [7];
    //                     break;
    //                 case 4:
    //                     $status = [8,9,10];
    //                     break;
    //                 default:
    //                     $status = [2,3,4,5,6,7,8,9,10,11];
    //             }
    //             $tasks = $tasks->whereIn('task.status',$status);
    //         }

    //         $tasks = $tasks->where('task.status','>=',2)->where('task.status','<=',11)->orderBy('task.created_at','desc')->paginate($data['limit'])->toArray();
    //         $status = [
    //             2=>'审核中',
    //             3=>'定时发布',
    //             4=>'投稿中',
    //             5=>'选稿中',
    //             6=>'选稿中',
    //             7=>'交付中',
    //             8=>'待评价',
    //             9=>'已结束',
    //             10=>'已结束',
    //             11=>'维权中'
    //         ];
    //         if(isset($data['status'])){
    //             $tasks['workStatus'] = $data['status'];
    //         } else{
    //             $tasks['workStatus'] = 0;
    //         }
    //         foreach($tasks['data'] as $k=>$v){
    //             $tasks['data'][$k]['status'] = $status[$v['status']];
    //         }
    //     }else{
    //         $tasks = [];
    //     }
    //     return $this->formateResponse(1000,'success',$tasks);
    // }

    /**我的已接任务
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myAcceptTask(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'per_page' => 'required',
            'page' => 'required',
        ],[
            'per_page.required' => '参数不完整：per_page',
            'page.required' => '参数不完整：page',
        ]);
        $error = $validator->errors()->all();
        if(count($error)) return $this->formateResponse(1001, $error[0]);
        $tokenInfo = Crypt::decrypt(urldecode($request->input('token')));
        $page = $request->get('page');
        $per_page = $request->get('per_page');
        $start = $per_page*($page-1);
        $list = WorkModel::where('work.uid',$tokenInfo['uid'])
                            ->where('work.status','!=',-1)
                            ->leftJoin('task','task.id','=','work.task_id')
                            ->leftJoin('users','users.id','=','work.uid')
                            ->select('task.id','task.title','task.created_at','task.identify','users.name','users.head_img','task.status')
                            ->orderBy('created_at','desc')
                            ->skip($start)
                            ->take($per_page)
                            ->get();
        if(count($list)){
            $list = $list->toArray();
            $url = ConfigModel::getConfigByAlias('site_url');
            foreach($list as &$list_single){
                if(!empty($list_single['head_img'])) $list_single['image'] = $url['rule'].$list_single['head_img'];
                $list_single['state'] = $this->status[$list_single['status']];
            }
            return $this->formateResponse(1000,'success',array('list'=>$list));
        }else{
            return $this->formateResponse(2000,'暂无数据');
        }
    }


    //创建任务
    /*public function createTask(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'title' => 'required',
            'desc' => 'required',
            'cate_id' => 'required',
            'bounty' => 'required|numeric',
            'worker_num' => 'required|integer|min:1',
            'province' => 'required',
            'city' => 'required',
            'delivery_deadline' => 'required',
            'begin_at' => 'required',
            'phone' => 'required'
        ],[
            'title.required' => '请填写任务标题',
            'desc.required' => '请填写任务描述',
            'cate_id.required' => '请选择行业类型',
            'bounty.required' => '请输入您的预算',
            'bounty.numeric' => '请输入正确的预算格式',
            'worker_num.required' => '中标人数不能为空',
            'worker_num.integer' => '中标人数必须为整形',
            'worker_num.min' => '中标人数最少为1人',

            'province.required' => '请选择省份',
            'city.required' => '请选择城市',
            'delivery_deadline.required' => '请选择投稿截止时间',
            'begin_at.required' => '请选择任务开始时间',
            'phone.required' => '请输入手机号'
        ]);

        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(2001,'输入的信息有误', $error);
        }
        if(strtotime($data['begin_at']) < time()){
            if($data['begin_at'] == date('Y-m-d',time())){
                $data['begin_at'] = date('Y-m-d H:i:s');
            }
            else{
                return $this->formateResponse(2003,'任务开始时间不得小于当前时间');
            }
        }
        if(strtotime($data['delivery_deadline']) <= strtotime($data['begin_at'])){
            return $this->formateResponse(2004,'截稿时间必须大于发布时间一天');
        }
        $taskTypeInfo = TaskTypeModel::where('alias','xuanshang')->select('id')->first();
        $slutype = $request->input('slutype',1);

        $arrTaskInfo = array(
            'uid' => $this->uid,
            'title' => $data['title'],
            'desc' => $data['desc'],
            'cate_id' => $data['cate_id'],
            'bounty' => $data['bounty'],
            'show_cash' => $data['bounty'],
            'worker_num' => $data['worker_num'],
            'province' => $data['province'],
            'city' => $data['city'],
            'delivery_deadline' => $data['delivery_deadline'],
            'status' =>  ($slutype == 1) ? 1 : 0 ,
            'begin_at' => $data['begin_at'],
            'type_id' => $taskTypeInfo->id,
            'phone' => $data['phone']
        );
        $file_id = $request->get('file_id');
        $result = DB::transaction(function() use ($arrTaskInfo,$file_id){
            $task = TaskModel::create($arrTaskInfo);
            if(!empty($file_id)){
                
                $file_able_ids = AttachmentModel::fileAble($file_id);
                $file_able_ids = array_flatten($file_able_ids);

                foreach($file_able_ids as $v){
                    $attachment_data = [
                        'task_id'=>$task['id'],
                        'attachment_id'=>$v,
                        'created_at'=>date('Y-m-d H:i:s', time()),
                    ];
                    TaskAttachmentModel::create($attachment_data);
                }
                
                $attachmentModel = new AttachmentModel();
                $attachmentModel->statusChange($file_able_ids);
            }

            $taskInfo = TaskModel::findById($task['id']);

            return $taskInfo;
        });
        if($result){
            return $this->formateResponse(1000,'success',$result);
        }else{
            return $this->formateResponse(2002,'创建失败');
        }
    }*/

    //投标
    /*public function createWinBidWork(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'desc' => 'required|str_length:2048'
        ],[
            'desc.required' => '请输入稿件描述',
            'desc.str_length'=> '字数超过限制',
        ]);
        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(2001,'参数有误', $error);
        }

        
        $data['status'] = 0;
        $result = $this->isWorkAble($data['task_id']);
        if($result['status'] == 0){
            return $this->formateResponse(2002,$result['message']);
        }

        $data['uid'] = $this->uid;
        $data['desc'] = e($data['desc']);
        $data['created_at'] = date('Y-m-d H:i:s');

        $workModel = new WorkModel();
        $result = $workModel->workCreate($data);

        if(!$result){
            return $this->formateResponse(2003,'投稿失败');
        }

        $param = self::workWinBind($result);

        if(isset($param['code']) && $param['code']=='1000'){
            return $this->formateResponse($param['code'],$param['msg']);
        }

    }*/

    //新增单个中标
    static function workWinBind($id)
    {
        $work = WorkModel::where('id',$id)->first();
        if(!$work){
            $param['code'] = '2001';
            $param['msg'] = '未找到对应的稿件信息';
            return $param;
        }

        $task = TaskModel::where('id',$work->task_id)->first();
        $work_num = WorkModel::where('task_id',$work->task_id)->where('status',1)->count();
        /*if($work->uid != $task->uid){
            $param['code'] = '2002';
            $param['msg'] = '你不是任务发布者，无权操作！';
            return $param;
        }*/
        if($task->worker_num > $work_num){
            $data = array(
                'task_id' => $work->task_id,
                'work_id' => $id,
                'worker_num' => $task->worker_num,
                'win_bid_num' => $work_num
            );
            $work_model = new WorkModel();
            $result = $work_model->winBid($data);
            if($result){
                $param['code'] = '1000';
                $param['msg'] = 'success';
            }else{
                $param['code'] = '2001';
                $param['msg'] = '网络错误';
            }
        }else{
            $param['code'] = '2003';
            $param['msg'] = '当前中标人数已满';
        }

        return $param;
    }

    //交付稿件
    /*public function createDeliveryWork(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'desc' => 'required|str_length:2048'
        ],[
            'desc.required' => '请输入稿件描述',
            'desc.str_length'=> '字数超过限制',
        ]);
        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(2001,'参数有误', $error);
        }

        $able = WorkModel::isWinBid($data['task_id'],$this->uid);
        if(!$able){
            return $this->formateResponse(2001,'你的稿件没有中标不能通过交付');
        }
        
        $is_delivery = WorkModel::where('task_id',$data['task_id'])
            ->where('uid',$this->uid)
            ->where('status','>=',2)->first();
        if($is_delivery){
            return $this->formateResponse(2003,'你已交付过稿件');
        }

        $data['uid'] = $this->uid;
        $data['status'] = 2;
        $data['created_at'] = date('Y-m-d H:i:s',time());

        $result = WorkModel::delivery($data);

        if($result){
            return $this->formateResponse(1000,'success');
        }else{
            return $this->formateResponse(2004,'交付失败');
        }
    }*/


    public function applauseRate(Request $request)
    {
        $applauseRate = \CommonClass::applauseRate($request->get('uid'));
        $data = array(
            'applauseRate' => $applauseRate,
        );
        return $this->formateResponse(1000,'success',$data);
    }


    //某稿件中标
    public function workWinBid(Request $request)
    {
        $id = $request->get('id');
        $work = WorkModel::where('id',$id)->first();
        if(!$work){
            return $this->formateResponse(1001,'未找到对应的稿件信息');
        }
        
        $task = TaskModel::where('id',$work->task_id)->first();
        $work_num = WorkModel::where('task_id',$work->task_id)->where('status',1)->count();
        if($this->uid != $task->uid){
            return $this->formateResponse(1001,'你不是任务发布者，无权操作！');
        }
        if($task->worker_num > $work_num){
            $data = array(
                'task_id' => $work->task_id,
                'work_id' => $id,
                'worker_num' => $task->worker_num,
                'win_bid_num' => $work_num
            );
            $work_model = new WorkModel();
            $result = $work_model->winBid($data);
            if($result){
                return $this->formateResponse(1000,'success');
            }else{
                return $this->formateResponse(1001,'网络错误');
            }
        }else{
            return $this->formateResponse(1001,'当前中标人数已满');
        }

    }

    //稿件验收
    /*public function deliveryWorkAgree(Request $request)
    {
        $data = $request->all();
        $work_id = intval($data['work_id']);
        $work = WorkModel::where('id',$work_id)->first();
        if(!$work){
            return $this->formateResponse(2003,'此稿件不存在');
        }
        $task = TaskModel::where('id',$work->task_id)->first();

        
        if($task->uid != $this->uid){
            return $this->formateResponse(2001,'你不是雇主，无权操作');
        }

        $work = WorkModel::where('task_id',$work->task_id)->where('uid',$work->uid)->where('status',2)->first();
        
        if($work->status != 2){
            return $this->formateResponse(2002,'当前稿件不具备验收资格');
        }
        
        $worker_num = $task->worker_num;
        
        $win_check = WorkModel::where('task_id',$work->task_id)->where('status','>',3)->count();

        $data['worker_num'] = $worker_num;
        $data['win_check'] = $win_check;
        $data['task_id'] = $work->task_id;
        $data['uid'] = $work->uid;
        $data['work_id'] = $work->id;

        $workModel = new WorkModel();
        $result = $workModel->workCheck($data);
        if($result){
            return $this->formateResponse(1000,'success');
        }else{
            return $this->formateResponse(2004,'failure');
        }
    }*/

    //雇主或猎人维权
   /* public function deliveryWorkRight(Request $request)
    {
        if(!$request->get('work_id') or !$request->get('desc')){
            return $this->formateResponse(2003,'传送参数不能为空');
        }
        $data = $request->all();
        $work_id = intval($data['work_id']);
        $work = WorkModel::where('id',$work_id)->first();
        $task = TaskModel::where('id',$work->task_id)->first();

        if(($work->uid != $this->uid) && ($task->uid != $this->uid)){
            return $this->formateResponse(2001,'你不具备维权资格');
        }

        if($work->uid == $this->uid){
            $data['role'] = 0;
            $data['from_uid'] = $this->uid;
            $data['to_uid'] = $task->uid;
        }
        if($task->uid == $this->uid){
            $data['role'] = 1;
            $data['from_uid'] = $this->uid;
            $data['to_uid'] = $work->uid;
        }
        $data['status'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s',time());

        $result = TaskRightsModel::rightCreate($data);
        if($result){
            return $this->formateResponse(1000,'success');
        }else{
            return $this->formateResponse(2002,'维权失败');
        }
    }*/

    //任意用户对稿件评论（稿件指的是）
    public function commentCreate(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'comment'=>'required',
            'task_id'=>'required',
            'work_id'=>'required',
        ],[
            'comment.required' => '回复内容不能为空',
            'task_id.required' => '所属任务id不能为空',
            'work_id.required' => '所属稿件id不能为空'
        ]);
        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,$error[0], $error);
        }
        $data['comment'] = e($data['comment']);
        $data['uid'] = $this->uid;
        $userDetail = UserDetailModel::where('uid',$this->uid)->first();
        $data['nickname'] = $userDetail->nickname;
        $data['created_at'] = date('Y-m-d H:i:s');

        $workComment = WorkCommentModel::create($data);
        $result = WorkCommentModel::where('id',$workComment->id)->first();
        if($result){
            $result->avatar = $userDetail->avatar;
            return $this->formateResponse(1000,'success',$result);
        }else{
            return $this->formateResponse(1001,'网络错误');
        }
    }

    //雇主对任务的稿件评价
    /*public function evaluateCreate(Request $request)
    {
        $data = $request->all();
        
        $work = WorkModel::where('task_id',$data['task_id'])
            ->where('uid',$this->uid)
            ->where('status',3)
            ->first();
        $task = TaskModel::where('id',$data['task_id'])->first();
        if(!$work && $task->uid != $this->uid){
            return $this->formateResponse(2001,'你没有评价此稿件的权限');
        }
        
        $data['from_uid'] = $this->uid;
        $data['comment'] = e($data['comment']);

        if($work)
        {
            $data['to_uid'] = $task->uid;
            $data['comment_by'] = 0;
        }
        if($task->uid == $this->uid)
        {
            $work = WorkModel::where('id',$data['work_id'])->first();
            $data['to_uid'] = $work['uid'];
            $data['comment_by'] = 1;
        }

        $is_evaluate =  CommentModel::where('from_uid',$this->uid)
            ->where('task_id',$data['task_id'])->where('to_uid',$data['to_uid'])
            ->first();
        if($is_evaluate){
            return $this->formateResponse(2002,'你已评论过此稿件');
        }
        $data['created_at'] = date('Y-m-d H:i:s',time());

        $comment = CommentModel::create($data);
        $evaluateInfo =  CommentModel::where('from_uid',$data['to_uid'])
            ->where('task_id',$data['task_id'])->where('to_uid',$this->uid)
            ->first();
        if(!empty($evaluateInfo)){
            TaskModel::where('id',$data['task_id'])->update(['status' => 9]);
        }
        $result = CommentModel::where('id',$comment['id'])->first();
        if($comment){
            return $this->formateResponse(1000,'success',$result);
        }else{
            return $this->formateResponse(2003,'评论失败');
        }
    }*/

    //获取稿件的评价
    public function getEvaluate(Request $request)
    {
        $work_id = $request->get('work_id');
        $workInfo = WorkModel::where(['id' => $work_id,'status' => 1])->first();
        if(empty($workInfo)){
            return $this->formateResponse(1001,'传送参数错误');
        }
        $work = WorkModel::where('task_id',$workInfo->task_id)->where('uid',$workInfo->uid)->where('status',3)->first();
        if(!$work){
            return $this->formateResponse(1001,'稿件交易未完成，暂无评价信息');
        }
        $task = TaskModel::where('id',$work->task_id)->first();
        
        if(($this->uid != $work->uid) && ($this->uid != $task->uid)){
            return $this->formateResponse(1001,'你没有查看该稿件评价信息的权限');
        }
        if($this->uid == $work->uid){
            $evaluate = CommentModel::where('task_id',$task->id)->where('from_uid',$task->uid)->first();
        }
        if($this->uid == $task->uid){
            $evaluate = CommentModel::where('task_id',$task->id)->where('from_uid',$work->uid)->first();
        }
        if($evaluate){
            return $this->formateResponse(1000,'success',$evaluate);
        }else{
            return $this->formateResponse(1001,'暂无相关评价信息');
        }
    }

    //投稿条件
    public function isWorkAble($task_id)
    {
        $data = array(
            'status' => 1,
            'message' => '',
        );
        if(!$this->uid){
            $data['status'] = 0;
            $data['message'] = '请先登录';
        }
        $task = TaskModel::where('id',$task_id)->first();
        if($task){
            
            if($task->uid == $this->uid){
                $data['status'] = 0;
                $data['message'] = '你是任务发布者，无法投稿';
            }
            
            $work = WorkModel::where('task_id',$task_id)->where('uid',$this->uid)->first();
            if($work){
                $data['status'] = 0;
                $data['message'] = '你已投稿或中标';
            }
        }else{
            $data['status'] = 0;
            $data['message'] = '任务不存在，无法投稿';
        }

        return $data;
    }

    //文件上传
    public function fileUpload(Request $request)
    {
        $file = $request->file('file');
        
        $attachment = \FileClass::uploadFile($file,'task');
        $attachment = json_decode($attachment, true);
        
        if($attachment['code']!=200)
        {
            return $this->formateResponse(1001,$attachment['message']);
        }
        $attachment_data = array_add($attachment['data'], 'status', 1);
        $attachment_data['created_at'] = date('Y-m-d H:i:s', time());
        
        $result = AttachmentModel::create($attachment_data);
        $data = AttachmentModel::where('id',$result['id'])->first();
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        if(isset($data)){
            $data->url = $data->url?$domain->rule.'/'.$data->url:$data->url;
        }
        if($result){
            return $this->formateResponse(1000,'success',$data);
        }else{
            return $this->formateResponse(1001,'文件上传失败');
        }
    }

    //删除附件
    public function fileDelete(Request $request)
    {
        $id = $request->get('id');
        $result = AttachmentModel::del($id,$this->uid);
        if($result){
            return $this->formateResponse(1000,'success');
        }else{
            return $this->formateResponse(1001,'附件删除失败');
        }
    }


    
    public function noPubTask(Request $request){
        $tasks = TaskModel::whereIn('task.status',[0,1])
            ->where('task.uid',$this->uid)
            ->select('task.*','cate.name as cate_name')
            ->leftjoin('cate','task.cate_id','=','cate.id')
            ->orderBy('task.created_at','desc')
            ->paginate()->toArray();
        return $this->formateResponse(1000,'success',$tasks);
    }


    
    public function agreeDelivery(Request $request){
        if(!$request->get('task_id') or !$request->get('id')){
            return $this->formateResponse(1001,'传送参数不能为空');
        }
        $deliveryInfo = [];
        $userInfo = UserModel::select('users.name')
            ->leftjoin('task','users.id','=','task.uid')
            ->where('task.id',intval($request->get('task_id')))
            ->where('task.uid',intval($this->uid))
            ->first();
        if(!isset($userInfo)){
            return $this->formateResponse(1001,'传送任务id错误');
        }
        $deliveryInfo['gname'] = $userInfo->name;
        $serverInfo = UserModel::select('users.name','work.id','work.desc')
            ->leftjoin('work','users.id','=','work.uid')
            ->where('work.uid',intval($request->get('id')))
            ->where('work.task_id',intval($request->get('task_id')))
            ->where('work.status','>=','2')
            ->first();
        if(!isset($serverInfo)){
            return $this->formateResponse(1001,'传送威客id错误');
        }
        $deliveryInfo['wname'] = $serverInfo->name;
        $deliveryInfo['desc'] = $serverInfo->desc;
        $attachIds = WorkAttachmentModel::where('task_id',intval($request->get('task_id')))
            ->where('work_id',$serverInfo->id)
            ->select('attachment_id')
            ->get()
            ->toArray();
        $attachInfo = [];
        if(isset($attachIds)){
            $attachIds = array_flatten($attachIds);
            $attachInfo = AttachmentModel::whereIn('id',$attachIds)
                ->select('url')
                ->get()
                ->toArray();
            $attachInfo = array_flatten($attachInfo);
            $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
            foreach($attachInfo as $k=>$v){
                $attachInfo[$k] = $attachInfo[$k]?$domain->rule.'/'.$attachInfo[$k]:$attachInfo[$k];
            }
        }
        $deliveryInfo['attachInfo'] = $attachInfo;
        return $this->formateResponse(1000,'获取协议信息成功',$deliveryInfo);

    }


    
    public function guestDelivery(Request $request){
        if(!$request->get('task_id')){
            return $this->formateResponse(1001,'传送参数不能为空');
        }
        $deliveryInfo = [];
        $userInfo = UserModel::select('users.name')
            ->leftjoin('task','users.id','=','task.uid')
            ->where('task.id',intval($request->get('task_id')))
            ->first();
        if(!isset($userInfo)){
            return $this->formateResponse(1001,'传送任务id错误');
        }
        $deliveryInfo['gname'] = $userInfo->name;
        $serverInfo = UserModel::select('users.name','work.id','work.desc')
            ->leftjoin('work','users.id','=','work.uid')
            ->where('work.uid',intval($this->uid))
            ->where('work.task_id',intval($request->get('task_id')))
            ->where('work.status','>=','2')
            ->first();
        if(!isset($serverInfo)){
            return $this->formateResponse(1001,'传送威客id错误');
        }
        $deliveryInfo['wname'] = $serverInfo->name;
        $deliveryInfo['desc'] = $serverInfo->desc;
        $attachIds = WorkAttachmentModel::where('task_id',intval($request->get('task_id')))
            ->where('work_id',$serverInfo->id)
            ->select('attachment_id')
            ->get()
            ->toArray();
        $attachInfo = [];
        if(isset($attachIds)){
            $attachIds = array_flatten($attachIds);
            $attachInfo = AttachmentModel::whereIn('id',$attachIds)
                ->select('url')
                ->get()
                ->toArray();
            $attachInfo = array_flatten($attachInfo);
            $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
            foreach($attachInfo as $k=>$v){
                $attachInfo[$k] = $attachInfo[$k]?$domain->rule.'/'.$attachInfo[$k]:$attachInfo[$k];
            }
        }
        $deliveryInfo['attachInfo'] = $attachInfo;
        return $this->formateResponse(1000,'获取协议信息成功',$deliveryInfo);
    }


    //不用删
    public function createTaskTest(Request $request)
    {
        $count = TaskWaterModel::where('status',2)->count();
        $len = 100;
        $num = $len - $count;
        if($num > 0){
            for($i =1;$i<$num;$i++){
                TaskWaterModel::createTask();
            }
        }

        return $this->formateResponse(1000,'success');
    }




}