<?php

namespace  App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;

class UserGameLogModel extends Model
{
    const LOG_IS_READ_NULL = 0;//没有查看
    const LOG_IS_READ_TRUE = 1;//查看

    const LOG_TYPE_CREATE_INFO = 1;//创建个人信息
    //const LOG_TYPE_EXIT_TEAM = 11;//退出战队

    const LOG_TYPE_CREATE_TEAM = 2;//创建战队
    const LOG_TYPE_REPORT_TEAM = 21;//战队报名

    const LOG_SYSTEM_TYPE_KICK_TEAM = 3;//被踢
    const LOG_SYSTEM_TYPE_DISBANDED_TEAM = 31;//解散战队

    protected $table = 'user_game_log';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id','to_id','is_read','content','create_at','type'
    ];

    public $timestamps = false;

    //创建记录
    static function createOne($uid,$type,$content,$is_read = self::LOG_IS_READ_TRUE)
    {
        $param = [
            'to_id'=>$uid,
            'is_read' => $is_read,
            'content' => $content,
            'type'=> $type,
            'create_at'=>date("Y-m-d H:i:s")
        ];
        $date = self::create($param);
        return $date;
    }

    //获取内容
    static function content($name,$type)
    {
        $time = date("Y-m-d");
        $content = array(
            self::LOG_TYPE_CREATE_INFO => $name.'于'.$time.'注册了王者荣耀游戏信息',
            //self::LOG_TYPE_EXIT_TEAM => $name.'于'.$time.'退出了战队',

            self::LOG_TYPE_CREATE_TEAM => $name.'于'.$time.'创建了战队',
            self::LOG_TYPE_REPORT_TEAM => $name.'于'.$time.'参与了战队报名',

            self::LOG_SYSTEM_TYPE_KICK_TEAM => '您被队长踢出了战队，请重新选择战队',
            self::LOG_SYSTEM_TYPE_DISBANDED_TEAM => '您所在的战队被解散，请重新选择战队',
        );
        //dd($content[$type]);
        return  isset($content[$type]) ? $content[$type] : '';
    }

    //判断某条件下某角色是否存在
    static function userIsExist($where)
    {
        $data = self::where($where)->first();
        return $data;
    }

    //获取用户基本信息
    static function getUserInfo($uid)
    {
        //dd($uid);
        //$data = [];
        foreach($uid as $id){
            $data[] = self::where('uid',$id)
                ->leftJoin('users','user_game_info.uid','=','users.id')
                ->select('users.id','users.name','users.mobile','user_game_info.game_name','user_game_info.game_server')
                ->first();
        }
        //dd($data);
        return $data;
    }


}
